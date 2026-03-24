<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\PurchaseModifyingCost;
use App\Models\Sell;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $purchases = $this->purchaseQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category', 'modifyingCosts'])
            ->get();

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category'])
            ->get();

        $averageCostByVehicle = $this->averageUnitCostByVehicle($selectedLocationIds->all(), $filters['date_to'] ?? null);
        $estimatedCostOfSales = (float) $sells->sum(function (Sell $sell) use ($averageCostByVehicle) {
            return $sell->quantity * (float) ($averageCostByVehicle[$sell->vehicle_id] ?? 0);
        });

        $totalPurchaseSpend = (float) $purchases->sum(fn (Purchase $purchase) => $purchase->grand_total);
        $totalSalesRevenue = (float) $sells->sum('selling_price_to_customer');
        $estimatedProfit = $totalSalesRevenue - $estimatedCostOfSales;

        $vehicleProfitability = $sells
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehicleSells, $vehicleId) use ($averageCostByVehicle) {
                /** @var \App\Models\Sell $firstSale */
                $firstSale = $vehicleSells->first();
                $vehicle = $firstSale->vehicle;
                $soldQuantity = (int) $vehicleSells->sum('quantity');
                $revenue = (float) $vehicleSells->sum('selling_price_to_customer');
                $averageUnitCost = (float) ($averageCostByVehicle[$vehicleId] ?? 0);
                $estimatedCost = $averageUnitCost * $soldQuantity;

                return [
                    'vehicle' => $this->stackedCell(
                        $vehicle?->display_name ?: 'Unknown vehicle',
                        ($vehicle?->brand?->name ?: 'No brand') . ' / ' . ($vehicle?->category?->name ?: 'No category')
                    ),
                    'sold_quantity' => $soldQuantity,
                    'revenue' => $this->money($revenue),
                    'average_unit_cost' => $this->money($averageUnitCost),
                    'estimated_cost' => $this->money($estimatedCost),
                    'estimated_profit' => $this->money($revenue - $estimatedCost),
                ];
            })
            ->sortByDesc(fn (array $row) => $this->numericValue($row['estimated_profit']))
            ->values()
            ->all();

        return $this->renderReport(
            'Profit / Loss Report',
            'reports.profit-loss',
            'Estimated branch profitability for the selected period.',
            [
                $this->summaryCard('Purchase Spend', $this->money($totalPurchaseSpend), 'Total purchase cost in the selected period.', 'danger', 'bi bi-bag-check'),
                $this->summaryCard('Sales Revenue', $this->money($totalSalesRevenue), 'Total sales value in the selected period.', 'success', 'bi bi-cash-stack'),
                $this->summaryCard('Estimated Cost of Sales', $this->money($estimatedCostOfSales), 'Average purchase cost per unit applied to sold items.', 'warning', 'bi bi-calculator'),
                $this->summaryCard('Estimated Profit / Loss', $this->money($estimatedProfit), $estimatedProfit >= 0 ? 'Positive margin for the selected period.' : 'Negative margin for the selected period.', $estimatedProfit >= 0 ? 'primary' : 'secondary', 'bi bi-graph-up-arrow'),
            ],
            [
                [
                    'title' => 'Product Profitability',
                    'description' => 'Revenue and estimated profit grouped by sold vehicle/product.',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'sold_quantity', 'label' => 'Sold Qty'],
                        ['key' => 'revenue', 'label' => 'Revenue'],
                        ['key' => 'average_unit_cost', 'label' => 'Avg Cost / Unit'],
                        ['key' => 'estimated_cost', 'label' => 'Estimated Cost'],
                        ['key' => 'estimated_profit', 'label' => 'Estimated Profit / Loss'],
                    ],
                    'rows' => $vehicleProfitability,
                    'empty' => 'No sales are available for the selected date range.',
                ],
            ],
            $filters,
            $activeLocation,
            'This report estimates cost of sales using the average purchase cost per unit for each vehicle in the active location.'
        );
    }

    public function purchaseSale(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $purchases = $this->purchaseQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category', 'modifyingCosts'])
            ->get();

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category'])
            ->get();

        $dailyPurchases = $purchases->groupBy(fn (Purchase $purchase) => optional($purchase->purchasing_date)->format('Y-m-d'));
        $dailySales = $sells->groupBy(fn (Sell $sell) => optional($sell->selling_date)->format('Y-m-d'));

        $dailyMovement = $dailyPurchases
            ->keys()
            ->merge($dailySales->keys())
            ->filter()
            ->unique()
            ->sortDesc()
            ->map(function (string $date) use ($dailyPurchases, $dailySales) {
                $purchaseRows = $dailyPurchases->get($date, collect());
                $saleRows = $dailySales->get($date, collect());
                $purchaseTotal = (float) $purchaseRows->sum(fn (Purchase $purchase) => $purchase->grand_total);
                $saleTotal = (float) $saleRows->sum('selling_price_to_customer');

                return [
                    'date' => $this->formatDateString($date),
                    'purchase_records' => $purchaseRows->count(),
                    'purchased_units' => (int) $purchaseRows->sum('quantity'),
                    'purchase_total' => $this->money($purchaseTotal),
                    'sale_records' => $saleRows->count(),
                    'sold_units' => (int) $saleRows->sum('quantity'),
                    'sale_total' => $this->money($saleTotal),
                    'net_flow' => $this->money($saleTotal - $purchaseTotal),
                ];
            })
            ->values()
            ->all();

        $recentPurchases = $purchases
            ->sortByDesc(fn (Purchase $purchase) => optional($purchase->purchasing_date)?->timestamp ?? 0)
            ->take(10)
            ->map(fn (Purchase $purchase) => [
                'date' => $purchase->purchasing_date?->format('d M Y') ?: 'N/A',
                'vehicle' => $this->stackedCell(
                    $purchase->vehicle?->display_name ?: 'Unknown vehicle',
                    ($purchase->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($purchase->vehicle?->category?->name ?: 'No category')
                ),
                'party' => $this->stackedCell($purchase->name, $purchase->mobile_number ?: 'Mobile not added'),
                'quantity' => $purchase->quantity,
                'amount' => $this->money((float) $purchase->grand_total),
            ])
            ->values()
            ->all();

        $recentSales = $sells
            ->sortByDesc(fn (Sell $sell) => optional($sell->selling_date)?->timestamp ?? 0)
            ->take(10)
            ->map(fn (Sell $sell) => [
                'date' => $sell->selling_date?->format('d M Y') ?: 'N/A',
                'vehicle' => $this->stackedCell(
                    $sell->vehicle?->display_name ?: 'Unknown vehicle',
                    ($sell->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($sell->vehicle?->category?->name ?: 'No category')
                ),
                'party' => $this->stackedCell($sell->name, $sell->mobile_number ?: 'Mobile not added'),
                'quantity' => $sell->quantity,
                'amount' => $this->money((float) $sell->selling_price_to_customer),
            ])
            ->values()
            ->all();

        return $this->renderReport(
            'Purchase & Sale Report',
            'reports.purchase-sale',
            'Combined purchasing and selling activity for the active branch.',
            [
                $this->summaryCard('Purchase Records', (string) $purchases->count(), 'Number of purchase entries in the selected period.', 'primary', 'bi bi-bag-check'),
                $this->summaryCard('Purchased Units', (string) $purchases->sum('quantity'), 'Total units purchased.', 'info', 'bi bi-box-arrow-in-down'),
                $this->summaryCard('Sale Records', (string) $sells->count(), 'Number of sale entries in the selected period.', 'success', 'bi bi-cash-stack'),
                $this->summaryCard('Sold Units', (string) $sells->sum('quantity'), 'Total units sold.', 'warning', 'bi bi-box-arrow-up'),
            ],
            [
                [
                    'title' => 'Daily Movement Summary',
                    'description' => 'Day-wise comparison between purchases and sales.',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'purchase_records', 'label' => 'Purchase Records'],
                        ['key' => 'purchased_units', 'label' => 'Purchased Units'],
                        ['key' => 'purchase_total', 'label' => 'Purchase Total'],
                        ['key' => 'sale_records', 'label' => 'Sale Records'],
                        ['key' => 'sold_units', 'label' => 'Sold Units'],
                        ['key' => 'sale_total', 'label' => 'Sale Total'],
                        ['key' => 'net_flow', 'label' => 'Net Flow'],
                    ],
                    'rows' => $dailyMovement,
                    'empty' => 'No purchase or sale records are available for the selected period.',
                ],
                [
                    'title' => 'Recent Purchases',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'party', 'label' => 'Supplier / Owner'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Amount'],
                    ],
                    'rows' => $recentPurchases,
                    'empty' => 'No purchases are available for the selected period.',
                ],
                [
                    'title' => 'Recent Sales',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'party', 'label' => 'Customer'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Amount'],
                    ],
                    'rows' => $recentSales,
                    'empty' => 'No sales are available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function supplierCustomer(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $purchases = $this->purchaseQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle', 'modifyingCosts'])
            ->get();

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with('vehicle')
            ->get();

        $supplierRows = $purchases
            ->groupBy(fn (Purchase $purchase) => mb_strtolower(trim($purchase->name . '|' . $purchase->mobile_number)))
            ->map(function (Collection $rows) {
                /** @var \App\Models\Purchase $first */
                $first = $rows->first();

                return [
                    'name' => $this->stackedCell($first->name, $first->mobile_number ?: 'Mobile not added'),
                    'transactions' => $rows->count(),
                    'quantity' => (int) $rows->sum('quantity'),
                    'amount' => $this->money((float) $rows->sum(fn (Purchase $purchase) => $purchase->grand_total)),
                    'latest' => optional($rows->sortByDesc('purchasing_date')->first()->purchasing_date)->format('d M Y') ?: 'N/A',
                ];
            })
            ->sortByDesc('transactions')
            ->values()
            ->all();

        $customerRows = $sells
            ->groupBy(fn (Sell $sell) => mb_strtolower(trim($sell->name . '|' . $sell->mobile_number)))
            ->map(function (Collection $rows) {
                /** @var \App\Models\Sell $first */
                $first = $rows->first();

                return [
                    'name' => $this->stackedCell($first->name, $first->mobile_number ?: 'Mobile not added'),
                    'transactions' => $rows->count(),
                    'quantity' => (int) $rows->sum('quantity'),
                    'amount' => $this->money((float) $rows->sum('selling_price_to_customer')),
                    'latest' => optional($rows->sortByDesc('selling_date')->first()->selling_date)->format('d M Y') ?: 'N/A',
                ];
            })
            ->sortByDesc('transactions')
            ->values()
            ->all();

        return $this->renderReport(
            'Supplier & Customer Report',
            'reports.supplier-customer',
            'Relationship summary for purchase suppliers/owners and sale customers.',
            [
                $this->summaryCard('Suppliers / Owners', (string) count($supplierRows), 'Unique supplier or owner records in the selected period.', 'primary', 'bi bi-person-vcard'),
                $this->summaryCard('Customers', (string) count($customerRows), 'Unique customer records in the selected period.', 'success', 'bi bi-people'),
                $this->summaryCard('Purchase Value', $this->money((float) $purchases->sum(fn (Purchase $purchase) => $purchase->grand_total)), 'Total value sourced from suppliers/owners.', 'warning', 'bi bi-currency-exchange'),
                $this->summaryCard('Sales Value', $this->money((float) $sells->sum('selling_price_to_customer')), 'Total value sold to customers.', 'info', 'bi bi-cash-coin'),
            ],
            [
                [
                    'title' => 'Supplier / Owner Summary',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Supplier / Owner'],
                        ['key' => 'transactions', 'label' => 'Transactions'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Purchase Total'],
                        ['key' => 'latest', 'label' => 'Latest Purchase'],
                    ],
                    'rows' => $supplierRows,
                    'empty' => 'No suppliers or owners were found for the selected period.',
                ],
                [
                    'title' => 'Customer Summary',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Customer'],
                        ['key' => 'transactions', 'label' => 'Transactions'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Sale Total'],
                        ['key' => 'latest', 'label' => 'Latest Sale'],
                    ],
                    'rows' => $customerRows,
                    'empty' => 'No customers were found for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function stock()
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $vehicles = Vehicle::query()
            ->with([
                'brand',
                'category',
                'purchases' => fn ($query) => $query
                    ->whereIn('location_id', $selectedLocationIds->all())
                    ->with('modifyingCosts')
                    ->latest('purchasing_date'),
                'sells' => fn ($query) => $query
                    ->whereIn('location_id', $selectedLocationIds->all())
                    ->latest('selling_date'),
            ])
            ->withStockForLocation($selectedLocationIds->all())
            ->orderBy('name')
            ->get()
            ->map(function (Vehicle $vehicle) {
                $purchaseRows = $vehicle->purchases;
                $totalPurchaseCost = (float) $purchaseRows->sum(fn (Purchase $purchase) => $purchase->grand_total);
                $totalPurchasedQuantity = max((int) $purchaseRows->sum('quantity'), 1);
                $averageUnitCost = $purchaseRows->isNotEmpty()
                    ? $totalPurchaseCost / $totalPurchasedQuantity
                    : 0;

                return [
                    'vehicle' => $this->stackedCell(
                        $vehicle->display_name,
                        $vehicle->brand->name . ' / ' . $vehicle->category->name
                    ),
                    'purchased' => $vehicle->purchased_quantity,
                    'sold' => $vehicle->sold_quantity,
                    'available' => $vehicle->available_stock_quantity,
                    'average_cost' => $this->money($averageUnitCost),
                    'stock_value' => $this->money($averageUnitCost * $vehicle->available_stock_quantity),
                    'status' => $this->badgeCell($vehicle->stock_status, $vehicle->stock_badge_class),
                ];
            });

        return $this->renderReport(
            'Stock Report',
            'reports.stock',
            'Current stock snapshot for the active branch.',
            [
                $this->summaryCard('Catalog Items', (string) $vehicles->count(), 'Total products in the catalog.', 'primary', 'bi bi-bicycle'),
                $this->summaryCard('Purchased Units', (string) $vehicles->sum('purchased'), 'Units received into this branch.', 'info', 'bi bi-box-arrow-in-down'),
                $this->summaryCard('Sold Units', (string) $vehicles->sum('sold'), 'Units sold from this branch.', 'danger', 'bi bi-box-arrow-up'),
                $this->summaryCard('Available Stock', (string) $vehicles->sum('available'), 'Units currently available.', 'success', 'bi bi-box-seam'),
            ],
            [
                [
                    'title' => 'Branch Stock Position',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'purchased', 'label' => 'Purchased'],
                        ['key' => 'sold', 'label' => 'Sold'],
                        ['key' => 'available', 'label' => 'Available'],
                        ['key' => 'average_cost', 'label' => 'Avg Cost / Unit'],
                        ['key' => 'stock_value', 'label' => 'Stock Value'],
                        ['key' => 'status', 'label' => 'Status'],
                    ],
                    'rows' => $vehicles->all(),
                    'empty' => 'No stock records are available for this location.',
                ],
            ],
            [],
            $activeLocation,
            null,
            false
        );
    }

    public function trendingProducts(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category'])
            ->get();

        $rows = $sells
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehicleSells) {
                /** @var \App\Models\Sell $first */
                $first = $vehicleSells->first();
                $quantity = (int) $vehicleSells->sum('quantity');
                $revenue = (float) $vehicleSells->sum('selling_price_to_customer');

                return [
                    'vehicle' => $this->stackedCell(
                        $first->vehicle?->display_name ?: 'Unknown vehicle',
                        ($first->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($first->vehicle?->category?->name ?: 'No category')
                    ),
                    'sale_records' => $vehicleSells->count(),
                    'sold_quantity' => $quantity,
                    'revenue' => $this->money($revenue),
                    'average_unit_price' => $this->money($quantity > 0 ? $revenue / $quantity : 0),
                    'last_sold' => optional($vehicleSells->sortByDesc('selling_date')->first()->selling_date)->format('d M Y') ?: 'N/A',
                ];
            })
            ->sortByDesc('sold_quantity')
            ->values();

        $topProductName = data_get($rows->first(), 'vehicle.plain', 'N/A');

        return $this->renderReport(
            'Trending Products',
            'reports.trending-products',
            'Top-selling vehicle/products for the selected period.',
            [
                $this->summaryCard('Products Sold', (string) $rows->count(), 'Unique products sold in the selected period.', 'primary', 'bi bi-stars'),
                $this->summaryCard('Sold Units', (string) $sells->sum('quantity'), 'Total sold quantity in the selected period.', 'success', 'bi bi-graph-up'),
                $this->summaryCard('Sales Revenue', $this->money((float) $sells->sum('selling_price_to_customer')), 'Total revenue from sold items.', 'warning', 'bi bi-cash-stack'),
                $this->summaryCard('Top Product', $topProductName, 'Best performer by sold quantity.', 'info', 'bi bi-trophy'),
            ],
            [
                [
                    'title' => 'Trending Product Ranking',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'sale_records', 'label' => 'Sale Records'],
                        ['key' => 'sold_quantity', 'label' => 'Sold Qty'],
                        ['key' => 'revenue', 'label' => 'Revenue'],
                        ['key' => 'average_unit_price', 'label' => 'Avg Sale / Unit'],
                        ['key' => 'last_sold', 'label' => 'Last Sold'],
                    ],
                    'rows' => $rows->all(),
                    'empty' => 'No sales data is available to rank products for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function items()
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $vehicles = Vehicle::query()
            ->with([
                'brand',
                'category',
                'purchases' => fn ($query) => $query
                    ->where('location_id', $activeLocation->id)
                    ->latest('purchasing_date'),
                'sells' => fn ($query) => $query
                    ->where('location_id', $activeLocation->id)
                    ->latest('selling_date'),
            ])
            ->withStockForLocation($selectedLocationIds->all())
            ->orderBy('name')
            ->get()
            ->map(function (Vehicle $vehicle) {
                return [
                    'vehicle' => $this->stackedCell(
                        $vehicle->display_name,
                        $vehicle->brand->name . ' / ' . $vehicle->category->name
                    ),
                    'registration' => $vehicle->registration_number ?: 'Not added',
                    'purchased' => $vehicle->purchased_quantity,
                    'sold' => $vehicle->sold_quantity,
                    'available' => $vehicle->available_stock_quantity,
                    'latest_purchase' => optional($vehicle->purchases->first()?->purchasing_date)->format('d M Y') ?: 'N/A',
                    'latest_sale' => optional($vehicle->sells->first()?->selling_date)->format('d M Y') ?: 'N/A',
                    'status' => $this->badgeCell($vehicle->stock_status, $vehicle->stock_badge_class),
                ];
            });

        $inStockCount = $vehicles->filter(fn (array $row) => data_get($row, 'status.value') === 'In Stock')->count();
        $outOfStockCount = $vehicles->filter(fn (array $row) => data_get($row, 'status.value') === 'Out of Stock')->count();
        $notPurchasedCount = $vehicles->filter(fn (array $row) => data_get($row, 'status.value') === 'Not Purchased')->count();

        return $this->renderReport(
            'Items Report',
            'reports.items',
            'Catalog-wide item status for the active branch.',
            [
                $this->summaryCard('Catalog Items', (string) $vehicles->count(), 'Total products in the catalog.', 'primary', 'bi bi-boxes'),
                $this->summaryCard('In Stock', (string) $inStockCount, 'Products with available stock.', 'success', 'bi bi-box-seam'),
                $this->summaryCard('Out of Stock', (string) $outOfStockCount, 'Products with zero available stock after purchase.', 'warning', 'bi bi-exclamation-triangle'),
                $this->summaryCard('Not Purchased', (string) $notPurchasedCount, 'Products never purchased in this branch.', 'secondary', 'bi bi-clock-history'),
            ],
            [
                [
                    'title' => 'Item Listing',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'registration', 'label' => 'Registration'],
                        ['key' => 'purchased', 'label' => 'Purchased'],
                        ['key' => 'sold', 'label' => 'Sold'],
                        ['key' => 'available', 'label' => 'Available'],
                        ['key' => 'latest_purchase', 'label' => 'Latest Purchase'],
                        ['key' => 'latest_sale', 'label' => 'Latest Sale'],
                        ['key' => 'status', 'label' => 'Status'],
                    ],
                    'rows' => $vehicles->all(),
                    'empty' => 'No items are available in the catalog.',
                ],
            ],
            [],
            $activeLocation,
            null,
            false
        );
    }

    public function productPurchase(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $purchases = $this->purchaseQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category', 'modifyingCosts'])
            ->get();

        $rows = $purchases
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehiclePurchases) {
                /** @var \App\Models\Purchase $first */
                $first = $vehiclePurchases->first();
                $totalQuantity = (int) $vehiclePurchases->sum('quantity');
                $totalSpend = (float) $vehiclePurchases->sum(fn (Purchase $purchase) => $purchase->grand_total);

                return [
                    'vehicle' => $this->stackedCell(
                        $first->vehicle?->display_name ?: 'Unknown vehicle',
                        ($first->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($first->vehicle?->category?->name ?: 'No category')
                    ),
                    'records' => $vehiclePurchases->count(),
                    'quantity' => $totalQuantity,
                    'purchase_total' => $this->money($totalSpend),
                    'average_cost' => $this->money($totalQuantity > 0 ? $totalSpend / $totalQuantity : 0),
                    'latest_purchase' => optional($vehiclePurchases->sortByDesc('purchasing_date')->first()->purchasing_date)->format('d M Y') ?: 'N/A',
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->all();

        return $this->renderReport(
            'Product Purchase Report',
            'reports.product-purchase',
            'Purchase activity grouped by vehicle/product.',
            [
                $this->summaryCard('Purchase Records', (string) $purchases->count(), 'Number of purchase records in the selected period.', 'primary', 'bi bi-bag-check'),
                $this->summaryCard('Products Purchased', (string) count($rows), 'Unique products purchased in the selected period.', 'info', 'bi bi-boxes'),
                $this->summaryCard('Purchased Units', (string) $purchases->sum('quantity'), 'Total units purchased.', 'warning', 'bi bi-box-arrow-in-down'),
                $this->summaryCard('Total Spend', $this->money((float) $purchases->sum(fn (Purchase $purchase) => $purchase->grand_total)), 'Total purchase cost including modifying costs.', 'danger', 'bi bi-wallet2'),
            ],
            [
                [
                    'title' => 'Purchase by Product',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'records', 'label' => 'Records'],
                        ['key' => 'quantity', 'label' => 'Purchased Qty'],
                        ['key' => 'purchase_total', 'label' => 'Purchase Total'],
                        ['key' => 'average_cost', 'label' => 'Avg Cost / Unit'],
                        ['key' => 'latest_purchase', 'label' => 'Latest Purchase'],
                    ],
                    'rows' => $rows,
                    'empty' => 'No product purchase activity is available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function productSell(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category'])
            ->get();

        $rows = $sells
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehicleSells) {
                /** @var \App\Models\Sell $first */
                $first = $vehicleSells->first();
                $totalQuantity = (int) $vehicleSells->sum('quantity');
                $totalRevenue = (float) $vehicleSells->sum('selling_price_to_customer');

                return [
                    'vehicle' => $this->stackedCell(
                        $first->vehicle?->display_name ?: 'Unknown vehicle',
                        ($first->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($first->vehicle?->category?->name ?: 'No category')
                    ),
                    'records' => $vehicleSells->count(),
                    'quantity' => $totalQuantity,
                    'sale_total' => $this->money($totalRevenue),
                    'average_price' => $this->money($totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0),
                    'latest_sale' => optional($vehicleSells->sortByDesc('selling_date')->first()->selling_date)->format('d M Y') ?: 'N/A',
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->all();

        return $this->renderReport(
            'Product Sell Report',
            'reports.product-sell',
            'Sales activity grouped by vehicle/product.',
            [
                $this->summaryCard('Sale Records', (string) $sells->count(), 'Number of sale records in the selected period.', 'success', 'bi bi-cash-stack'),
                $this->summaryCard('Products Sold', (string) count($rows), 'Unique products sold in the selected period.', 'primary', 'bi bi-boxes'),
                $this->summaryCard('Sold Units', (string) $sells->sum('quantity'), 'Total units sold.', 'warning', 'bi bi-box-arrow-up'),
                $this->summaryCard('Total Revenue', $this->money((float) $sells->sum('selling_price_to_customer')), 'Total value sold to customers.', 'info', 'bi bi-cash-coin'),
            ],
            [
                [
                    'title' => 'Sales by Product',
                    'columns' => [
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'records', 'label' => 'Records'],
                        ['key' => 'quantity', 'label' => 'Sold Qty'],
                        ['key' => 'sale_total', 'label' => 'Sale Total'],
                        ['key' => 'average_price', 'label' => 'Avg Sale / Unit'],
                        ['key' => 'latest_sale', 'label' => 'Latest Sale'],
                    ],
                    'rows' => $rows,
                    'empty' => 'No product sale activity is available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function purchasePayment(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $purchases = $this->purchaseQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category', 'modifyingCosts'])
            ->get();

        $statusSummary = $this->paymentSummaryCards(
            $purchases,
            fn (Purchase $purchase) => (float) $purchase->grand_total,
            fn (Purchase $purchase) => $purchase->payment_status ?? 'unpaid',
            'purchase'
        );

        $rows = $purchases
            ->sortByDesc(fn (Purchase $purchase) => optional($purchase->purchasing_date)?->timestamp ?? 0)
            ->map(fn (Purchase $purchase) => [
                'date' => $purchase->purchasing_date?->format('d M Y') ?: 'N/A',
                'party' => $this->stackedCell($purchase->name, $purchase->mobile_number ?: 'Mobile not added'),
                'vehicle' => $this->stackedCell(
                    $purchase->vehicle?->display_name ?: 'Unknown vehicle',
                    ($purchase->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($purchase->vehicle?->category?->name ?: 'No category')
                ),
                'quantity' => $purchase->quantity,
                'amount' => $this->money((float) $purchase->grand_total),
                'status' => $this->badgeCell($purchase->payment_status_label, $purchase->payment_status_badge_class),
                'method' => $purchase->payment_method_label,
                'information' => $purchase->payment_information ?: 'Not provided',
            ])
            ->values()
            ->all();

        return $this->renderReport(
            'Purchase Payment Report',
            'reports.purchase-payment',
            'Payment status overview for purchase records.',
            $statusSummary,
            [
                [
                    'title' => 'Purchase Payment Details',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'party', 'label' => 'Supplier / Owner'],
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Amount'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'method', 'label' => 'Method'],
                        ['key' => 'information', 'label' => 'Payment Information'],
                    ],
                    'rows' => $rows,
                    'empty' => 'No purchase payment records are available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function sellPayment(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $sells = $this->sellQuery($selectedLocationIds->all(), $filters)
            ->with(['vehicle.brand', 'vehicle.category', 'location'])
            ->get();

        $statusSummary = $this->paymentSummaryCards(
            $sells,
            fn (Sell $sell) => (float) $sell->selling_price_to_customer,
            fn (Sell $sell) => $sell->payment_status ?? 'unpaid',
            'sale'
        );

        $rows = $sells
            ->sortByDesc(fn (Sell $sell) => optional($sell->selling_date)?->timestamp ?? 0)
            ->map(fn (Sell $sell) => [
                'date' => $sell->selling_date?->format('d M Y') ?: 'N/A',
                'invoice' => $sell->invoice_number,
                'party' => $this->stackedCell($sell->name, $sell->mobile_number ?: 'Mobile not added'),
                'vehicle' => $this->stackedCell(
                    $sell->vehicle?->display_name ?: 'Unknown vehicle',
                    ($sell->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($sell->vehicle?->category?->name ?: 'No category')
                ),
                'quantity' => $sell->quantity,
                'amount' => $this->money((float) $sell->selling_price_to_customer),
                'status' => $this->badgeCell($sell->payment_status_label, $sell->payment_status_badge_class),
                'method' => $sell->payment_method_label,
                'information' => $sell->payment_information ?: 'Not provided',
            ])
            ->values()
            ->all();

        return $this->renderReport(
            'Sell Payment Report',
            'reports.sell-payment',
            'Payment status overview for sale records.',
            $statusSummary,
            [
                [
                    'title' => 'Sale Payment Details',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'invoice', 'label' => 'Invoice'],
                        ['key' => 'party', 'label' => 'Customer'],
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                        ['key' => 'amount', 'label' => 'Amount'],
                        ['key' => 'status', 'label' => 'Status'],
                        ['key' => 'method', 'label' => 'Method'],
                        ['key' => 'information', 'label' => 'Payment Information'],
                    ],
                    'rows' => $rows,
                    'empty' => 'No sale payment records are available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation
        );
    }

    public function expense(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);

        $expenses = PurchaseModifyingCost::query()
            ->with(['purchase.vehicle.brand', 'purchase.vehicle.category'])
            ->whereHas('purchase', function (Builder $query) use ($selectedLocationIds, $filters) {
                $query->whereIn('location_id', $selectedLocationIds->all());
                $this->applyDateRange($query, 'purchasing_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
            })
            ->get()
            ->filter(fn (PurchaseModifyingCost $expense) => $expense->purchase !== null)
            ->values();

        $reasonSummary = $expenses
            ->groupBy(fn (PurchaseModifyingCost $expense) => $expense->reason ?: 'Unspecified')
            ->map(fn (Collection $rows, string $reason) => [
                'reason' => $reason,
                'entries' => $rows->count(),
                'amount' => $this->money((float) $rows->sum('cost')),
            ])
            ->sortByDesc(fn (array $row) => $this->numericValue($row['amount']))
            ->values()
            ->all();

        $expenseDetails = $expenses
            ->sortByDesc(fn (PurchaseModifyingCost $expense) => optional($expense->purchase?->purchasing_date)?->timestamp ?? 0)
            ->map(fn (PurchaseModifyingCost $expense) => [
                'date' => optional($expense->purchase?->purchasing_date)->format('d M Y') ?: 'N/A',
                'reason' => $expense->reason ?: 'Unspecified',
                'vehicle' => $this->stackedCell(
                    $expense->purchase?->vehicle?->display_name ?: 'Unknown vehicle',
                    ($expense->purchase?->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($expense->purchase?->vehicle?->category?->name ?: 'No category')
                ),
                'supplier' => $expense->purchase?->name ?: 'Unknown supplier',
                'amount' => $this->money((float) $expense->cost),
            ])
            ->values()
            ->all();

        return $this->renderReport(
            'Expense Report',
            'reports.expense',
            'Expense report based on purchase modifying costs.',
            [
                $this->summaryCard('Expense Entries', (string) $expenses->count(), 'Number of modifying cost entries in the selected period.', 'primary', 'bi bi-receipt-cutoff'),
                $this->summaryCard('Total Expenses', $this->money((float) $expenses->sum('cost')), 'Total modifying cost amount.', 'danger', 'bi bi-cash'),
                $this->summaryCard('Expense Reasons', (string) count($reasonSummary), 'Unique expense reasons in the selected period.', 'warning', 'bi bi-tags'),
                $this->summaryCard('Average Entry', $this->money($expenses->count() > 0 ? (float) $expenses->avg('cost') : 0), 'Average cost per expense entry.', 'info', 'bi bi-calculator'),
            ],
            [
                [
                    'title' => 'Expense by Reason',
                    'columns' => [
                        ['key' => 'reason', 'label' => 'Reason'],
                        ['key' => 'entries', 'label' => 'Entries'],
                        ['key' => 'amount', 'label' => 'Amount'],
                    ],
                    'rows' => $reasonSummary,
                    'empty' => 'No expense reasons are available for the selected period.',
                ],
                [
                    'title' => 'Expense Details',
                    'columns' => [
                        ['key' => 'date', 'label' => 'Date'],
                        ['key' => 'reason', 'label' => 'Reason'],
                        ['key' => 'vehicle', 'label' => 'Vehicle / Product'],
                        ['key' => 'supplier', 'label' => 'Supplier / Owner'],
                        ['key' => 'amount', 'label' => 'Amount'],
                    ],
                    'rows' => $expenseDetails,
                    'empty' => 'No expense details are available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation,
            'This report currently uses modifying costs from purchase records as branch expenses.'
        );
    }

    public function activityLog(Request $request)
    {
        $activeLocation = $this->getActiveLocation();
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
            return $this->missingLocationResponse();
        }

        $filters = $this->validatedDateFilters($request);
        $accessibleLocations = $this->getAccessibleLocations();

        $activities = collect()
            ->merge(
                $this->purchaseQuery($selectedLocationIds->all(), $filters, 'updated_at')
                    ->with('vehicle')
                    ->get()
                    ->map(fn (Purchase $purchase) => $this->activityRow(
                        $purchase->updated_at,
                        'Purchases',
                        $this->modelAction($purchase),
                        $purchase->vehicle?->display_name ?: 'Unknown vehicle',
                        'Supplier/Owner: ' . $purchase->name . ' | Qty: ' . $purchase->quantity . ' | Purchase date: ' . ($purchase->purchasing_date?->format('d M Y') ?: 'N/A')
                    ))
            )
            ->merge(
                $this->sellQuery($selectedLocationIds->all(), $filters, 'updated_at')
                    ->with('vehicle', 'location')
                    ->get()
                    ->map(fn (Sell $sell) => $this->activityRow(
                        $sell->updated_at,
                        'Sales',
                        $this->modelAction($sell),
                        $sell->invoice_number,
                        'Customer: ' . $sell->name . ' | Vehicle: ' . ($sell->vehicle?->display_name ?: 'Unknown vehicle') . ' | Sale date: ' . ($sell->selling_date?->format('d M Y') ?: 'N/A')
                    ))
            )
            ->merge(
                Vehicle::query()
                    ->with(['brand', 'category'])
                    ->when(true, function (Builder $query) use ($filters) {
                        $this->applyDateRange($query, 'updated_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                    })
                    ->get()
                    ->map(fn (Vehicle $vehicle) => $this->activityRow(
                        $vehicle->updated_at,
                        'Vehicles',
                        $this->modelAction($vehicle),
                        $vehicle->display_name,
                        ($vehicle->brand?->name ?: 'No brand') . ' / ' . ($vehicle->category?->name ?: 'No category')
                    ))
            )
            ->merge(
                Brand::query()
                    ->when(true, function (Builder $query) use ($filters) {
                        $this->applyDateRange($query, 'updated_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                    })
                    ->get()
                    ->map(fn (Brand $brand) => $this->activityRow(
                        $brand->updated_at,
                        'Brands',
                        $this->modelAction($brand),
                        $brand->name,
                        'Brand master data updated.'
                    ))
            )
            ->merge(
                Category::query()
                    ->when(true, function (Builder $query) use ($filters) {
                        $this->applyDateRange($query, 'updated_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                    })
                    ->get()
                    ->map(fn (Category $category) => $this->activityRow(
                        $category->updated_at,
                        'Categories',
                        $this->modelAction($category),
                        $category->name,
                        'Category master data updated.'
                    ))
            )
            ->merge(
                User::query()
                    ->with('roles')
                    ->when(true, function (Builder $query) use ($filters) {
                        $this->applyDateRange($query, 'updated_at', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                    })
                    ->get()
                    ->map(fn (User $user) => $this->activityRow(
                        $user->updated_at,
                        'Users',
                        $this->modelAction($user),
                        $user->name,
                        'Email: ' . $user->email . ' | Roles: ' . ($user->roles->pluck('name')->implode(', ') ?: 'No role')
                    ))
            )
            ->merge(
                $accessibleLocations
                    ->filter(function ($location) use ($filters) {
                        $dateFrom = $filters['date_from'] ?? null;
                        $dateTo = $filters['date_to'] ?? null;

                        if (! $dateFrom && ! $dateTo) {
                            return true;
                        }

                        if (! $location->updated_at) {
                            return false;
                        }

                        if ($dateFrom && $location->updated_at->lt(\Illuminate\Support\Carbon::parse($dateFrom)->startOfDay())) {
                            return false;
                        }

                        if ($dateTo && $location->updated_at->gt(\Illuminate\Support\Carbon::parse($dateTo)->endOfDay())) {
                            return false;
                        }

                        return true;
                    })
                    ->map(fn ($location) => $this->activityRow(
                        $location->updated_at,
                        'Locations',
                        $this->modelAction($location),
                        $location->display_name,
                        ($location->is_active ? 'Active' : 'Inactive') . ' branch record updated.'
                    ))
            )
            ->sortByDesc('sort_timestamp')
            ->take(60)
            ->values();

        return $this->renderReport(
            'Activity Log',
            'reports.activity-log',
            'Derived recent activity timeline based on create and update timestamps across core modules.',
            [
                $this->summaryCard('Activity Entries', (string) $activities->count(), 'Recent activity entries currently shown.', 'primary', 'bi bi-clock-history'),
                $this->summaryCard('Purchase Events', (string) $activities->where('module', 'Purchases')->count(), 'Purchase create/update activity.', 'info', 'bi bi-bag-check'),
                $this->summaryCard('Sale Events', (string) $activities->where('module', 'Sales')->count(), 'Sale create/update activity.', 'success', 'bi bi-cash-stack'),
                $this->summaryCard('Catalog & Admin Events', (string) $activities->whereNotIn('module', ['Purchases', 'Sales'])->count(), 'Vehicle, user, brand, category, and location updates.', 'warning', 'bi bi-diagram-3'),
            ],
            [
                [
                    'title' => 'Recent Activity',
                    'columns' => [
                        ['key' => 'date_time', 'label' => 'Date / Time'],
                        ['key' => 'module', 'label' => 'Module'],
                        ['key' => 'action', 'label' => 'Action'],
                        ['key' => 'reference', 'label' => 'Reference'],
                        ['key' => 'details', 'label' => 'Details'],
                    ],
                    'rows' => $activities->map(fn (array $activity) => [
                        'date_time' => $activity['date_time'],
                        'module' => $activity['module'],
                        'action' => $activity['action'],
                        'reference' => $activity['reference'],
                        'details' => $activity['details'],
                    ])->all(),
                    'empty' => 'No activity entries are available for the selected period.',
                ],
            ],
            $filters,
            $activeLocation,
            'This is a derived report. It summarizes record timestamps and does not replace a dedicated audit trail.'
        );
    }

    private function validatedDateFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }

    private function purchaseQuery(int|array $locationIds, array $filters, string $dateColumn = 'purchasing_date'): Builder
    {
        $query = Purchase::query()->whereIn('location_id', collect($locationIds)->map(fn ($id) => (int) $id)->filter()->all());
        $this->applyDateRange($query, $dateColumn, $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        return $query;
    }

    private function sellQuery(int|array $locationIds, array $filters, string $dateColumn = 'selling_date'): Builder
    {
        $query = Sell::query()->whereIn('location_id', collect($locationIds)->map(fn ($id) => (int) $id)->filter()->all());
        $this->applyDateRange($query, $dateColumn, $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        return $query;
    }

    private function applyDateRange(Builder $query, string $column, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }

    private function renderReport(
        string $title,
        string $routeName,
        string $description,
        array $summaryCards,
        array $sections,
        array $filters,
        $activeLocation,
        ?string $note = null,
        bool $showsDateFilters = true
    ) {
        return view('reports.show', [
            'businessSetting' => $this->getBusinessSetting(),
            'activeLocation' => $activeLocation,
            'reportTitle' => $title,
            'reportRouteName' => $routeName,
            'reportDescription' => $description,
            'reportNote' => $note,
            'summaryCards' => $summaryCards,
            'sections' => $sections,
            'dateFrom' => $filters['date_from'] ?? null,
            'dateTo' => $filters['date_to'] ?? null,
            'showsDateFilters' => $showsDateFilters,
        ]);
    }

    private function averageUnitCostByVehicle(int|array $locationIds, ?string $dateTo = null): Collection
    {
        $query = Purchase::query()
            ->whereIn('location_id', collect($locationIds)->map(fn ($id) => (int) $id)->filter()->all())
            ->with('modifyingCosts');

        if ($dateTo) {
            $query->whereDate('purchasing_date', '<=', $dateTo);
        }

        return $query
            ->get()
            ->groupBy('vehicle_id')
            ->map(function (Collection $purchases) {
                $quantity = max((int) $purchases->sum('quantity'), 1);
                $cost = (float) $purchases->sum(fn (Purchase $purchase) => $purchase->grand_total);

                return $cost / $quantity;
            });
    }

    private function paymentSummaryCards(Collection $records, callable $amountResolver, callable $statusResolver, string $context): array
    {
        $paidCount = $records->filter(fn ($record) => $statusResolver($record) === 'paid')->count();
        $partialCount = $records->filter(fn ($record) => $statusResolver($record) === 'partial')->count();
        $unpaidCount = $records->filter(fn ($record) => $statusResolver($record) === 'unpaid' || blank($statusResolver($record)))->count();
        $totalAmount = (float) $records->sum(fn ($record) => $amountResolver($record));

        return [
            $this->summaryCard(ucfirst($context) . ' Records', (string) $records->count(), 'Total records in the selected period.', 'primary', 'bi bi-journal-text'),
            $this->summaryCard('Paid', (string) $paidCount, 'Records marked as fully paid.', 'success', 'bi bi-check-circle'),
            $this->summaryCard('Partial', (string) $partialCount, 'Records marked as partially paid.', 'warning', 'bi bi-hourglass-split'),
            $this->summaryCard('Unpaid', (string) $unpaidCount, 'Records marked as unpaid or not set.', 'danger', 'bi bi-x-circle'),
            $this->summaryCard('Total Amount', $this->money($totalAmount), 'Total amount across the selected records.', 'info', 'bi bi-cash-coin'),
        ];
    }

    private function modelAction($model): array
    {
        $isCreated = $model->created_at && $model->updated_at && $model->created_at->equalTo($model->updated_at);

        return $this->badgeCell($isCreated ? 'Created' : 'Updated', $isCreated ? 'text-bg-success' : 'text-bg-warning');
    }

    private function activityRow($timestamp, string $module, array $action, string $reference, string $details): array
    {
        return [
            'sort_timestamp' => optional($timestamp)->timestamp ?? 0,
            'date_time' => optional($timestamp)->format('d M Y h:i A') ?: 'N/A',
            'module' => $module,
            'action' => $action,
            'reference' => $reference,
            'details' => $details,
        ];
    }

    private function summaryCard(string $label, string $value, string $hint, string $color, string $icon): array
    {
        return compact('label', 'value', 'hint', 'color', 'icon');
    }

    private function money(float $amount): string
    {
        return ($this->getBusinessSetting()->currency_code ?: 'BDT') . ' ' . number_format($amount, 2);
    }

    private function badgeCell(string $value, string $class = 'text-bg-secondary'): array
    {
        return [
            'type' => 'badge',
            'value' => $value,
            'class' => $class,
        ];
    }

    private function stackedCell(string $primary, ?string $secondary = null): array
    {
        $html = '<div class="fw-semibold">' . e($primary) . '</div>';

        if ($secondary) {
            $html .= '<div class="small text-muted">' . e($secondary) . '</div>';
        }

        return [
            'type' => 'html',
            'value' => $html,
            'plain' => $primary,
        ];
    }

    private function formatDateString(string $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)->format('d M Y');
    }

    private function numericValue(string $moneyValue): float
    {
        return (float) preg_replace('/[^0-9.-]/', '', $moneyValue);
    }
}


