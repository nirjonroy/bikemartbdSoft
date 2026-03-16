<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDocument;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::query()
            ->with(['vehicle.brand', 'vehicle.category'])
            ->withCount(['pictureDocuments as pictures_count'])
            ->withSum('modifyingCosts as modifying_costs_sum', 'cost')
            ->latest('purchasing_date')
            ->paginate(12);

        return view('purchases.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'purchases' => $purchases,
        ]);
    }

    public function create()
    {
        return view('purchases.create', $this->formViewData([
            'purchase' => new Purchase(),
        ]));
    }

    public function store(Request $request)
    {
        [$purchaseData, $modifyingCosts] = $this->validatedPurchaseData($request);

        $purchase = Purchase::create($purchaseData);

        $this->syncDocuments($request, $purchase);
        $this->syncModifyingCosts($purchase, $modifyingCosts);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vehicle.brand', 'vehicle.category', 'documents', 'modifyingCosts']);

        return view('purchases.show', $this->formViewData([
            'purchase' => $purchase,
        ]));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['vehicle.brand', 'vehicle.category', 'documents', 'modifyingCosts']);

        return view('purchases.edit', $this->formViewData([
            'purchase' => $purchase,
        ]));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $purchase->load('documents');

        [$purchaseData, $modifyingCosts] = $this->validatedPurchaseData($request);

        $purchase->update($purchaseData);

        $this->syncDocuments($request, $purchase);
        $this->syncModifyingCosts($purchase, $modifyingCosts);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('status', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->load('documents');

        $this->deleteDocuments($purchase->documents);
        $purchase->delete();

        return redirect()
            ->route('purchases.index')
            ->with('status', 'Purchase deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'singleDocumentTypes' => PurchaseDocument::SINGLE_TYPES,
            'vehicles' => Vehicle::query()->with(['brand', 'category'])->orderBy('name')->get(),
        ], $overrides);
    }

    private function validatedPurchaseData(Request $request): array
    {
        $modifyingCosts = collect($request->input('modifying_costs', []))
            ->map(fn ($row) => [
                'reason' => trim((string) ($row['reason'] ?? '')),
                'cost' => $row['cost'] ?? null,
            ])
            ->filter(fn ($row) => filled($row['reason']) || filled($row['cost']))
            ->values()
            ->all();

        $validator = Validator::make(
            array_merge($request->all(), ['modifying_costs' => $modifyingCosts]),
            [
                'vehicle_id' => ['required', 'exists:vehicles,id'],
                'name' => ['required', 'string', 'max:255'],
                'father_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:1000'],
                'mobile_number' => ['nullable', 'string', 'max:50'],
                'buying_price_from_owner' => ['required', 'numeric', 'min:0'],
                'purchasing_date' => ['required', 'date'],
                'extra_additional_note' => ['nullable', 'string', 'max:2000'],
                'pictures' => ['nullable', 'array'],
                'pictures.*' => ['nullable', 'image', 'max:5120'],
                'nid_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'registration_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'smart_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'tax_token' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'fitness_paper' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'insurance' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
                'remove_documents' => ['nullable', 'array'],
                'remove_documents.*' => ['string', 'in:' . implode(',', array_keys(PurchaseDocument::SINGLE_TYPES))],
                'remove_picture_ids' => ['nullable', 'array'],
                'remove_picture_ids.*' => ['integer'],
                'modifying_costs' => ['nullable', 'array'],
                'modifying_costs.*.reason' => ['required', 'string', 'max:255'],
                'modifying_costs.*.cost' => ['required', 'numeric', 'min:0'],
            ]
        );

        $validated = $validator->validate();

        $purchaseData = collect($validated)
            ->except(array_merge(
                ['pictures', 'remove_documents', 'remove_picture_ids', 'modifying_costs'],
                array_keys(PurchaseDocument::SINGLE_TYPES)
            ))
            ->all();

        return [$purchaseData, $modifyingCosts];
    }

    private function syncDocuments(Request $request, Purchase $purchase): void
    {
        $removeTypes = collect($request->input('remove_documents', []))
            ->filter(fn ($type) => array_key_exists($type, PurchaseDocument::SINGLE_TYPES));

        if ($removeTypes->isNotEmpty()) {
            $documentsToDelete = $purchase->documents()
                ->whereIn('type', $removeTypes->all())
                ->get();

            $this->deleteDocuments($documentsToDelete);
        }

        $removePictureIds = collect($request->input('remove_picture_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        if (! empty($removePictureIds)) {
            $picturesToDelete = $purchase->pictureDocuments()
                ->whereIn('id', $removePictureIds)
                ->get();

            $this->deleteDocuments($picturesToDelete);
        }

        foreach (array_keys(PurchaseDocument::SINGLE_TYPES) as $type) {
            if (! $request->hasFile($type)) {
                continue;
            }

            $this->deleteDocuments($purchase->documents()->where('type', $type)->get());
            $this->storeDocument($purchase, $type, $request->file($type));
        }

        if ($request->hasFile('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $this->storeDocument($purchase, PurchaseDocument::TYPE_PICTURE, $picture);
            }
        }
    }

    private function syncModifyingCosts(Purchase $purchase, array $modifyingCosts): void
    {
        $purchase->modifyingCosts()->delete();

        if (! empty($modifyingCosts)) {
            $purchase->modifyingCosts()->createMany($modifyingCosts);
        }
    }

    private function storeDocument(Purchase $purchase, string $type, $file): void
    {
        $path = $file->store("purchases/{$purchase->id}/{$type}", 'public');

        $purchase->documents()->create([
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
