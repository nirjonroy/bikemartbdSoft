@php
    $pictureDocument = $sell->exists ? $sell->documentFor('picture') : null;
    $singleDocumentTypes = collect($documentTypes)->except(['picture'])->all();
    $selectedVehicleId = old('vehicle_id', request('vehicle_id', $sell->vehicle_id));
    $selectedVehicle = $vehicles->firstWhere('id', (int) $selectedVehicleId);
    $selectedPaymentStatus = old('payment_status', $sell->payment_status ?: 'unpaid');
    $selectedPaymentMethod = old('payment_method', $sell->payment_method);
    $showStockInformation = $businessSetting->show_stock_information ?? true;
    $showQuantityFields = $businessSetting->show_quantity_fields ?? true;
    $selectedQuantity = (int) old('quantity', $sell->quantity ?? 1);
    $selectedSellingPrice = old('selling_price_to_customer', $sell->selling_price_to_customer);
    $selectedLatestPurchase = $selectedVehicle?->latestPurchase;
    $selectedLatestPurchaseQuantity = max((int) ($selectedLatestPurchase?->quantity ?? 0), 1);
    $selectedPurchaseExcludingTotal = $selectedLatestPurchase ? (float) $selectedLatestPurchase->buying_price_from_owner : null;
    $selectedPurchaseIncludingTotal = $selectedLatestPurchase ? (float) $selectedLatestPurchase->grand_total : null;
    $selectedPurchaseExcludingUnit = $selectedLatestPurchase ? $selectedPurchaseExcludingTotal / $selectedLatestPurchaseQuantity : null;
    $selectedPurchaseIncludingUnit = $selectedLatestPurchase ? $selectedPurchaseIncludingTotal / $selectedLatestPurchaseQuantity : null;
    $selectedCostExcludingForSale = $selectedPurchaseExcludingUnit !== null ? $selectedPurchaseExcludingUnit * max($selectedQuantity, 1) : null;
    $selectedCostIncludingForSale = $selectedPurchaseIncludingUnit !== null ? $selectedPurchaseIncludingUnit * max($selectedQuantity, 1) : null;
    $selectedProfitPreviewAvailable = $selectedLatestPurchase && is_numeric($selectedSellingPrice);
    $selectedProfitExcluding = $selectedProfitPreviewAvailable ? (float) $selectedSellingPrice - $selectedCostExcludingForSale : null;
    $selectedProfitIncluding = $selectedProfitPreviewAvailable ? (float) $selectedSellingPrice - $selectedCostIncludingForSale : null;
    $selectedProfitExcludingPercentage = $selectedProfitPreviewAvailable && $selectedCostExcludingForSale > 0
        ? ($selectedProfitExcluding / $selectedCostExcludingForSale) * 100
        : null;
    $selectedProfitIncludingPercentage = $selectedProfitPreviewAvailable && $selectedCostIncludingForSale > 0
        ? ($selectedProfitIncluding / $selectedCostIncludingForSale) * 100
        : null;
    $vehiclePricingData = $vehicles
        ->map(function ($vehicleOption) {
            $latestPurchase = $vehicleOption->latestPurchase;
            $purchaseQuantity = max((int) ($latestPurchase?->quantity ?? 0), 1);
            $purchaseExcludingTotal = $latestPurchase ? (float) $latestPurchase->buying_price_from_owner : null;
            $purchaseIncludingTotal = $latestPurchase ? (float) $latestPurchase->grand_total : null;

            return [
                'id' => (int) $vehicleOption->id,
                'brand_name' => $vehicleOption->brand->name,
                'category_name' => $vehicleOption->category->name,
                'registration_number' => $vehicleOption->registration_number ?: 'Not added',
                'engine_number' => $vehicleOption->engine_number ?: 'Not added',
                'purchased_quantity' => (int) $vehicleOption->purchased_quantity,
                'sold_quantity' => (int) $vehicleOption->sold_quantity,
                'available_stock_quantity' => (int) $vehicleOption->available_stock_quantity,
                'stock_status' => $vehicleOption->stock_status,
                'stock_badge_class' => $vehicleOption->stock_badge_class,
                'latest_purchase_date' => $latestPurchase?->purchasing_date?->format('d M Y'),
                'purchase_excluding_total' => $purchaseExcludingTotal,
                'purchase_including_total' => $purchaseIncludingTotal,
                'purchase_excluding_unit' => $latestPurchase ? round($purchaseExcludingTotal / $purchaseQuantity, 2) : null,
                'purchase_including_unit' => $latestPurchase ? round($purchaseIncludingTotal / $purchaseQuantity, 2) : null,
            ];
        })
        ->values()
        ->all();
@endphp

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Customer and Sale Information</h3>
    </div>
    <div class="card-body">
        @if ($vehicles->isEmpty())
            <div class="alert alert-warning">
                No vehicles are currently available for sale.
                Record a purchase first so the vehicle enters stock.
            </div>
        @else
            <div class="alert alert-info">
                {{ $showStockInformation ? ($showQuantityFields ? 'Sale entries reduce stock by the quantity you enter here. Quantity cannot exceed the available stock.' : 'Sale entries update stock automatically and still respect the available stock.') : ($showQuantityFields ? 'Enter the sale quantity for the selected vehicle.' : 'Record the sale for the selected vehicle.') }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label for="vehicle_id" class="form-label">Vehicle / Product</label>
                <select id="vehicle_id" name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                    <option value="">Select a vehicle</option>
                    @foreach ($vehicles as $vehicleOption)
                        <option value="{{ $vehicleOption->id }}" @selected((string) $selectedVehicleId === (string) $vehicleOption->id)>
                            {{ $vehicleOption->display_name }} | {{ $vehicleOption->brand->name }} / {{ $vehicleOption->category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Selected Vehicle Details</label>
                <div class="border rounded p-3 h-100 bg-light" id="selected-vehicle-details">
                    @if ($selectedVehicle)
                        <div class="fw-semibold">{{ $selectedVehicle->brand->name }} / {{ $selectedVehicle->category->name }}</div>
                        <div class="small text-muted">
                            Registration: {{ $selectedVehicle->registration_number ?: 'Not added' }} |
                            Engine: {{ $selectedVehicle->engine_number ?: 'Not added' }}
                        </div>
                        @if ($showStockInformation)
                            <div class="small text-muted mt-2">
                                Purchased: {{ $selectedVehicle->purchased_quantity }} |
                                Sold: {{ $selectedVehicle->sold_quantity }} |
                                Available: {{ $selectedVehicle->available_stock_quantity }}
                            </div>
                            <div class="small mt-2">
                                <span class="badge {{ $selectedVehicle->stock_badge_class }}">{{ $selectedVehicle->stock_status }}</span>
                            </div>
                        @endif
                        @if ($selectedLatestPurchase)
                            <div class="small text-muted mt-3">Latest purchase: {{ $selectedLatestPurchase->purchasing_date?->format('d M Y') }}</div>
                            <div class="small text-muted mt-1">
                                Excluding modifying cost: BDT {{ number_format($selectedPurchaseExcludingTotal, 2) }} total |
                                Unit: BDT {{ number_format($selectedPurchaseExcludingUnit, 2) }}
                            </div>
                            <div class="small text-muted mt-1">
                                Including modifying cost: BDT {{ number_format($selectedPurchaseIncludingTotal, 2) }} total |
                                Unit: BDT {{ number_format($selectedPurchaseIncludingUnit, 2) }}
                            </div>
                        @else
                            <div class="small text-warning mt-3">No purchase cost data found for this branch yet.</div>
                        @endif
                    @else
                        <div class="text-muted">Select a vehicle to connect this sale record.</div>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $sell->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="father_name" class="form-label">Father's Name</label>
                <input type="text" id="father_name" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $sell->father_name) }}">
            </div>

            <div class="col-md-4">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="text" id="mobile_number" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $sell->mobile_number) }}">
            </div>

            @if ($showQuantityFields)
                <div class="col-md-2">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input
                        type="number"
                        min="1"
                        step="1"
                        id="quantity"
                        name="quantity"
                        class="form-control @error('quantity') is-invalid @enderror"
                        value="{{ old('quantity', $sell->quantity ?? 1) }}"
                        required
                    >
                </div>
            @else
                <input type="hidden" id="quantity" name="quantity" value="{{ old('quantity', $sell->quantity ?? 1) }}">
            @endif

            <div class="col-md-3">
                <label for="selling_date" class="form-label">Selling Date</label>
                <input
                    type="date"
                    id="selling_date"
                    name="selling_date"
                    class="form-control @error('selling_date') is-invalid @enderror"
                    value="{{ old('selling_date', $sell->selling_date?->format('Y-m-d')) }}"
                    required
                >
            </div>

            <div class="col-md-3">
                <label for="selling_price_to_customer" class="form-label">Selling Price to Customer</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="selling_price_to_customer"
                    name="selling_price_to_customer"
                    class="form-control @error('selling_price_to_customer') is-invalid @enderror"
                    value="{{ old('selling_price_to_customer', $sell->selling_price_to_customer) }}"
                    required
                >
            </div>

            <div class="col-12">
                <label class="form-label">Profit Preview</label>
                <div class="border rounded p-3 bg-light" id="sale-profit-preview">
                    @if (! $selectedVehicle)
                        <div class="text-muted">Select a vehicle and enter a selling price to see the expected profit.</div>
                    @elseif (! $selectedLatestPurchase)
                        <div class="text-warning">Purchase cost data is not available for the selected vehicle in this branch yet.</div>
                    @else
                        <div class="small text-muted mb-3">
                            Based on latest purchase from {{ $selectedLatestPurchase->purchasing_date?->format('d M Y') }} and current sale quantity of {{ max($selectedQuantity, 1) }} unit(s).
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-white">
                                    <div class="text-uppercase small fw-semibold text-muted mb-2">Excluding Modifying Cost</div>
                                    <div class="small text-muted">Cost basis for current quantity</div>
                                    <div class="fw-semibold mb-2">BDT {{ number_format($selectedCostExcludingForSale, 2) }}</div>
                                    @if ($selectedProfitPreviewAvailable)
                                        <div class="small text-muted">Expected profit</div>
                                        <div class="fw-semibold {{ $selectedProfitExcluding >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($selectedProfitExcluding, 2) }}
                                        </div>
                                        <div class="small {{ $selectedProfitExcluding >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($selectedProfitExcludingPercentage, 2) }}%
                                        </div>
                                    @else
                                        <div class="small text-muted">Enter selling price to calculate profit.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-white">
                                    <div class="text-uppercase small fw-semibold text-muted mb-2">Including Modifying Cost</div>
                                    <div class="small text-muted">Cost basis for current quantity</div>
                                    <div class="fw-semibold mb-2">BDT {{ number_format($selectedCostIncludingForSale, 2) }}</div>
                                    @if ($selectedProfitPreviewAvailable)
                                        <div class="small text-muted">Expected profit</div>
                                        <div class="fw-semibold {{ $selectedProfitIncluding >= 0 ? 'text-success' : 'text-danger' }}">
                                            BDT {{ number_format($selectedProfitIncluding, 2) }}
                                        </div>
                                        <div class="small {{ $selectedProfitIncluding >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($selectedProfitIncludingPercentage, 2) }}%
                                        </div>
                                    @else
                                        <div class="small text-muted">Enter selling price to calculate profit.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <label for="payment_status" class="form-label">Payment Status</label>
                <select id="payment_status" name="payment_status" class="form-select @error('payment_status') is-invalid @enderror">
                    @foreach ($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $selectedPaymentStatus === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                    <option value="">Select payment method</option>
                    @foreach ($paymentMethodOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $selectedPaymentMethod === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $sell->address) }}</textarea>
            </div>

            <div class="col-12">
                <label for="payment_information" class="form-label">Payment Information</label>
                <textarea id="payment_information" name="payment_information" rows="3" class="form-control @error('payment_information') is-invalid @enderror" placeholder="Transaction id, account number, cheque details or other payment notes">{{ old('payment_information', $sell->payment_information) }}</textarea>
            </div>

            <div class="col-12">
                <label for="extra_additional_note" class="form-label">Extra Additional Note</label>
                <textarea id="extra_additional_note" name="extra_additional_note" rows="4" class="form-control @error('extra_additional_note') is-invalid @enderror">{{ old('extra_additional_note', $sell->extra_additional_note) }}</textarea>
            </div>
        </div>
    </div>
</div>
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">Picture and Documents</h3>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <label for="picture" class="form-label">Picture</label>
            <input type="file" id="picture" name="picture" class="form-control @error('picture') is-invalid @enderror" accept="image/*">

            @if ($pictureDocument)
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <a href="{{ $pictureDocument->url }}" target="_blank" class="small">Current picture</a>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_documents[]" value="picture" id="remove_picture">
                        <label class="form-check-label small" for="remove_picture">Remove picture</label>
                    </div>
                </div>
                <div class="mt-2">
                    <img src="{{ $pictureDocument->url }}" alt="Sell picture" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
                </div>
            @endif
        </div>

        <div class="row g-3">
            @foreach ($singleDocumentTypes as $field => $label)
                @php $document = $sell->exists ? $sell->documentFor($field) : null; @endphp
                <div class="col-md-6">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                    <input type="file" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,.pdf">

                    @if ($document)
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <a href="{{ $document->url }}" target="_blank" class="small">
                                Current file: {{ $document->original_name ?: basename($document->file_path) }}
                            </a>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_documents[]" value="{{ $field }}" id="remove_{{ $field }}">
                                <label class="form-check-label small" for="remove_{{ $field }}">Remove</label>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('sells.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const vehicleSelect = document.getElementById('vehicle_id');
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling_price_to_customer');
            const vehicleDetails = document.getElementById('selected-vehicle-details');
            const profitPreview = document.getElementById('sale-profit-preview');
            const vehicles = @json($vehiclePricingData);
            const vehicleMap = new Map(vehicles.map((vehicle) => [String(vehicle.id), vehicle]));
            const showStockInformation = @json($showStockInformation);
            const moneyFormatter = new Intl.NumberFormat('en-BD', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            if (!vehicleSelect || !quantityInput || !sellingPriceInput || !vehicleDetails || !profitPreview) {
                return;
            }

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            };

            const formatMoney = (value) => `BDT ${moneyFormatter.format(Number(value || 0))}`;
            const formatPercent = (value) => `${Number(value || 0).toFixed(2)}%`;
            const currentVehicle = () => vehicleMap.get(String(vehicleSelect.value || '')) || null;
            const currentQuantity = () => Math.max(parseInt(quantityInput.value || '0', 10) || 0, 0);
            const currentSellingPrice = () => {
                const parsed = parseFloat(sellingPriceInput.value || '');
                return Number.isFinite(parsed) ? parsed : null;
            };
            const profitClass = (value) => value > 0 ? 'text-success' : value < 0 ? 'text-danger' : 'text-muted';

            const vehicleDetailsMarkup = (vehicle) => {
                if (!vehicle) {
                    return '<div class="text-muted">Select a vehicle to connect this sale record.</div>';
                }

                const purchaseInfo = vehicle.purchase_including_total !== null
                    ? `
                        <div class="small text-muted mt-3">Latest purchase: ${escapeHtml(vehicle.latest_purchase_date || 'Not available')}</div>
                        <div class="small text-muted mt-1">
                            Excluding modifying cost: ${escapeHtml(formatMoney(vehicle.purchase_excluding_total))} total |
                            Unit: ${escapeHtml(formatMoney(vehicle.purchase_excluding_unit))}
                        </div>
                        <div class="small text-muted mt-1">
                            Including modifying cost: ${escapeHtml(formatMoney(vehicle.purchase_including_total))} total |
                            Unit: ${escapeHtml(formatMoney(vehicle.purchase_including_unit))}
                        </div>
                    `
                    : '<div class="small text-warning mt-3">No purchase cost data found for this branch yet.</div>';

                return `
                    <div class="fw-semibold">${escapeHtml(vehicle.brand_name)} / ${escapeHtml(vehicle.category_name)}</div>
                    <div class="small text-muted">
                        Registration: ${escapeHtml(vehicle.registration_number)} |
                        Engine: ${escapeHtml(vehicle.engine_number)}
                    </div>
                    ${showStockInformation ? `
                        <div class="small text-muted mt-2">
                            Purchased: ${escapeHtml(String(vehicle.purchased_quantity))} |
                            Sold: ${escapeHtml(String(vehicle.sold_quantity))} |
                            Available: ${escapeHtml(String(vehicle.available_stock_quantity))}
                        </div>
                        <div class="small mt-2">
                            <span class="badge ${vehicle.stock_badge_class}">${escapeHtml(vehicle.stock_status)}</span>
                        </div>
                    ` : ''}
                    ${purchaseInfo}
                `;
            };

            const profitCardMarkup = (title, costBasis, profitValue, profitPercentage) => `
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-white">
                        <div class="text-uppercase small fw-semibold text-muted mb-2">${escapeHtml(title)}</div>
                        <div class="small text-muted">Cost basis for current quantity</div>
                        <div class="fw-semibold mb-2">${escapeHtml(formatMoney(costBasis))}</div>
                        <div class="small text-muted">Expected profit</div>
                        <div class="fw-semibold ${profitClass(profitValue)}">${escapeHtml(formatMoney(profitValue))}</div>
                        <div class="small ${profitClass(profitValue)}">${escapeHtml(formatPercent(profitPercentage))}</div>
                    </div>
                </div>
            `;

            const previewWithoutPriceMarkup = (title, costBasis) => `
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-white">
                        <div class="text-uppercase small fw-semibold text-muted mb-2">${escapeHtml(title)}</div>
                        <div class="small text-muted">Cost basis for current quantity</div>
                        <div class="fw-semibold mb-2">${escapeHtml(formatMoney(costBasis))}</div>
                        <div class="small text-muted">Enter selling price to calculate profit.</div>
                    </div>
                </div>
            `;

            const profitPreviewMarkup = (vehicle) => {
                if (!vehicle) {
                    return '<div class="text-muted">Select a vehicle and enter a selling price to see the expected profit.</div>';
                }

                if (vehicle.purchase_including_total === null) {
                    return '<div class="text-warning">Purchase cost data is not available for the selected vehicle in this branch yet.</div>';
                }

                const quantity = currentQuantity();

                if (quantity <= 0) {
                    return '<div class="text-muted">Enter a valid sale quantity to calculate profit.</div>';
                }

                const excludingCost = Number(vehicle.purchase_excluding_unit) * quantity;
                const includingCost = Number(vehicle.purchase_including_unit) * quantity;
                const sellingPrice = currentSellingPrice();
                const intro = `
                    <div class="small text-muted mb-3">
                        Based on latest purchase from ${escapeHtml(vehicle.latest_purchase_date || 'Not available')} and current sale quantity of ${escapeHtml(String(quantity))} unit(s).
                    </div>
                `;

                if (sellingPrice === null) {
                    return `
                        ${intro}
                        <div class="row g-3">
                            ${previewWithoutPriceMarkup('Excluding Modifying Cost', excludingCost)}
                            ${previewWithoutPriceMarkup('Including Modifying Cost', includingCost)}
                        </div>
                    `;
                }

                const excludingProfit = sellingPrice - excludingCost;
                const includingProfit = sellingPrice - includingCost;
                const excludingPercentage = excludingCost > 0 ? (excludingProfit / excludingCost) * 100 : 0;
                const includingPercentage = includingCost > 0 ? (includingProfit / includingCost) * 100 : 0;

                return `
                    ${intro}
                    <div class="row g-3">
                        ${profitCardMarkup('Excluding Modifying Cost', excludingCost, excludingProfit, excludingPercentage)}
                        ${profitCardMarkup('Including Modifying Cost', includingCost, includingProfit, includingPercentage)}
                    </div>
                `;
            };

            const renderSaleMetrics = () => {
                const vehicle = currentVehicle();
                vehicleDetails.innerHTML = vehicleDetailsMarkup(vehicle);
                profitPreview.innerHTML = profitPreviewMarkup(vehicle);
            };

            vehicleSelect.addEventListener('change', renderSaleMetrics);
            quantityInput.addEventListener('input', renderSaleMetrics);
            sellingPriceInput.addEventListener('input', renderSaleMetrics);

            renderSaleMetrics();
        })();
    </script>
@endpush