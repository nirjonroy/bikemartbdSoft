<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Sell;
use App\Models\SellDocument;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SellController extends Controller
{
    public function index(Request $request)
    {
        $activeLocation = $this->getActiveLocation();

        if (! $activeLocation) {
            return $this->missingLocationResponse();
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $vehicleId = (int) ($filters['vehicle_id'] ?? 0);
        $brandId = (int) ($filters['brand_id'] ?? 0);
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $sells = Sell::query()
            ->where('location_id', $activeLocation->id)
            ->with(['vehicle.brand', 'vehicle.category', 'location'])
            ->withCount(['pictureDocuments as pictures_count'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sellQuery) use ($search) {
                    $sellQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                            $vehicleQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%")
                                ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($vehicleId > 0, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when($brandId > 0, fn ($query) => $query->whereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('brand_id', $brandId)))
            ->when($categoryId > 0, fn ($query) => $query->whereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('category_id', $categoryId)))
            ->when($dateFrom, fn ($query) => $query->whereDate('selling_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('selling_date', '<=', $dateTo))
            ->latest('selling_date')
            ->paginate(12)
            ->withQueryString();

        return view('sells.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'sells' => $sells,
            'search' => $search,
            'selectedVehicleId' => $vehicleId ?: null,
            'selectedBrandId' => $brandId ?: null,
            'selectedCategoryId' => $categoryId ?: null,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'hasFilters' => $search !== '' || $vehicleId > 0 || $brandId > 0 || $categoryId > 0 || filled($dateFrom) || filled($dateTo),
            'vehicles' => Vehicle::query()->with(['brand', 'category'])->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        if (! $this->getActiveLocation()) {
            return $this->missingLocationResponse();
        }

        return view('sells.create', $this->formViewData([
            'sell' => new Sell(),
        ]));
    }

    public function store(Request $request)
    {
        $activeLocation = $this->getActiveLocation();

        if (! $activeLocation) {
            return $this->missingLocationResponse();
        }

        $sellData = $this->validatedSellData($request, $activeLocation->id);
        $sellData['location_id'] = $activeLocation->id;

        $sell = Sell::create($sellData);

        $this->syncDocuments($request, $sell);

        return redirect()
            ->route('sells.show', $sell)
            ->with('status', 'Sale created successfully.');
    }

    public function show(Sell $sell)
    {
        $this->abortIfRecordNotInActiveLocation($sell->location_id);

        $sell->load(['vehicle.brand', 'vehicle.category', 'location', 'documents']);

        return view('sells.show', $this->formViewData([
            'sell' => $sell,
        ]));
    }

    public function edit(Sell $sell)
    {
        $this->abortIfRecordNotInActiveLocation($sell->location_id);

        $sell->load(['vehicle.brand', 'vehicle.category', 'location', 'documents']);

        return view('sells.edit', $this->formViewData([
            'sell' => $sell,
        ]));
    }

    public function update(Request $request, Sell $sell)
    {
        $activeLocation = $this->getActiveLocation();

        if (! $activeLocation) {
            return $this->missingLocationResponse();
        }

        $this->abortIfRecordNotInActiveLocation($sell->location_id);
        $sell->load('documents');

        $sellData = $this->validatedSellData($request, $activeLocation->id, $sell);
        $sellData['location_id'] = $activeLocation->id;

        $sell->update($sellData);

        $this->syncDocuments($request, $sell);

        return redirect()
            ->route('sells.show', $sell)
            ->with('status', 'Sale updated successfully.');
    }

    public function destroy(Sell $sell)
    {
        $this->abortIfRecordNotInActiveLocation($sell->location_id);
        $sell->load('documents');

        $this->deleteDocuments($sell->documents);
        $sell->delete();

        return redirect()
            ->route('sells.index')
            ->with('status', 'Sale deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        /** @var \App\Models\Sell|null $sell */
        $sell = $overrides['sell'] ?? null;
        $activeLocation = $this->getActiveLocation();

        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'documentTypes' => SellDocument::FILE_TYPES,
            'paymentStatusOptions' => Sell::PAYMENT_STATUSES,
            'paymentMethodOptions' => Sell::PAYMENT_METHODS,
            'vehicles' => $activeLocation
                ? $this->availableVehiclesForSale($sell, $activeLocation->id)
                : collect(),
        ], $overrides);
    }

    private function validatedSellData(Request $request, int $locationId, ?Sell $sell = null): array
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1'],
            'selling_price_to_customer' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'string', 'in:' . implode(',', array_keys(Sell::PAYMENT_STATUSES))],
            'payment_method' => ['nullable', 'string', 'in:' . implode(',', array_keys(Sell::PAYMENT_METHODS))],
            'payment_information' => ['nullable', 'string', 'max:2000'],
            'selling_date' => ['required', 'date'],
            'extra_additional_note' => ['nullable', 'string', 'max:2000'],
            'picture' => ['nullable', 'image', 'max:5120'],
            'registration_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'smart_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'nid_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'tax_token' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'fitness_paper' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'insurance' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_documents' => ['nullable', 'array'],
            'remove_documents.*' => ['string', 'in:' . implode(',', array_keys(SellDocument::FILE_TYPES))],
        ]);

        $validator->after(function ($validator) use ($request, $sell, $locationId) {
            $vehicleId = (int) $request->input('vehicle_id');
            $requestedQuantity = (int) $request->input('quantity');

            if (! $vehicleId || ! $requestedQuantity) {
                return;
            }

            $vehicle = Vehicle::query()
                ->withStockForLocation($locationId)
                ->find($vehicleId);

            if (! $vehicle) {
                return;
            }

            $availableQuantity = $vehicle->available_stock_quantity;

            if ($sell && $vehicleId === (int) $sell->vehicle_id) {
                $availableQuantity += (int) $sell->quantity;
            }

            if ($availableQuantity <= 0) {
                $validator->errors()->add('vehicle_id', 'Only vehicles that are currently in stock can be sold.');
                return;
            }

            if ($requestedQuantity > $availableQuantity) {
                $validator->errors()->add('quantity', "Only {$availableQuantity} item(s) are currently available in stock for this vehicle.");
            }
        });

        $validated = $validator->validate();

        return collect($validated)
            ->except(array_merge(['remove_documents'], array_keys(SellDocument::FILE_TYPES)))
            ->all();
    }

    private function availableVehiclesForSale(?Sell $sell = null, ?int $locationId = null)
    {
        $query = Vehicle::query()
            ->with(['brand', 'category'])
            ->orderBy('name')
            ->when(
                $locationId,
                fn ($vehicleQuery) => $vehicleQuery->withStockForLocation($locationId),
                fn ($vehicleQuery) => $vehicleQuery
                    ->withSum('purchases as purchased_quantity_total', 'quantity')
                    ->withSum('sells as sold_quantity_total', 'quantity')
            );

        return $query
            ->get()
            ->filter(function (Vehicle $vehicle) use ($sell) {
                if ($sell && (int) $sell->vehicle_id === (int) $vehicle->id) {
                    return true;
                }

                return $vehicle->isAvailableForSale();
            })
            ->values();
    }

    private function syncDocuments(Request $request, Sell $sell): void
    {
        $removeTypes = collect($request->input('remove_documents', []))
            ->filter(fn ($type) => array_key_exists($type, SellDocument::FILE_TYPES));

        if ($removeTypes->isNotEmpty()) {
            $this->deleteDocuments($sell->documents()->whereIn('type', $removeTypes->all())->get());
        }

        foreach (array_keys(SellDocument::FILE_TYPES) as $type) {
            if (! $request->hasFile($type)) {
                continue;
            }

            $this->deleteDocuments($sell->documents()->where('type', $type)->get());
            $this->storeDocument($sell, $type, $request->file($type));
        }
    }

    private function storeDocument(Sell $sell, string $type, $file): void
    {
        $path = $file->store("sells/{$sell->id}/{$type}", 'public');

        $sell->documents()->create([
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    private function deleteDocuments($documents): void
    {
        foreach ($documents as $document) {
            Storage::disk('public')->delete($document->file_path);
            $document->delete();
        }
    }
}
