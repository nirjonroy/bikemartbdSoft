<?php

namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\SellDocument;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SellController extends Controller
{
    public function index()
    {
        $sells = Sell::query()
            ->with(['vehicle.brand', 'vehicle.category'])
            ->withCount(['pictureDocuments as pictures_count'])
            ->latest('selling_date')
            ->paginate(12);

        return view('sells.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'sells' => $sells,
        ]);
    }

    public function create()
    {
        return view('sells.create', $this->formViewData([
            'sell' => new Sell(),
        ]));
    }

    public function store(Request $request)
    {
        $sellData = $this->validatedSellData($request);

        $sell = Sell::create($sellData);

        $this->syncDocuments($request, $sell);

        return redirect()
            ->route('sells.show', $sell)
            ->with('status', 'Sale created successfully.');
    }

    public function show(Sell $sell)
    {
        $sell->load(['vehicle.brand', 'vehicle.category', 'documents']);

        return view('sells.show', $this->formViewData([
            'sell' => $sell,
        ]));
    }

    public function edit(Sell $sell)
    {
        $sell->load(['vehicle.brand', 'vehicle.category', 'documents']);

        return view('sells.edit', $this->formViewData([
            'sell' => $sell,
        ]));
    }

    public function update(Request $request, Sell $sell)
    {
        $sell->load('documents');

        $sell->update($this->validatedSellData($request));

        $this->syncDocuments($request, $sell);

        return redirect()
            ->route('sells.show', $sell)
            ->with('status', 'Sale updated successfully.');
    }

    public function destroy(Sell $sell)
    {
        $sell->load('documents');

        $this->deleteDocuments($sell->documents);
        $sell->delete();

        return redirect()
            ->route('sells.index')
            ->with('status', 'Sale deleted successfully.');
    }

    private function formViewData(array $overrides = []): array
    {
        return array_merge([
            'businessSetting' => $this->getBusinessSetting(),
            'documentTypes' => SellDocument::FILE_TYPES,
            'vehicles' => Vehicle::query()->with(['brand', 'category'])->orderBy('name')->get(),
        ], $overrides);
    }

    private function validatedSellData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'selling_price_to_customer' => ['required', 'numeric', 'min:0'],
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

        $validated = $validator->validate();

        return collect($validated)
            ->except(array_merge(['remove_documents'], array_keys(SellDocument::FILE_TYPES)))
            ->all();
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
