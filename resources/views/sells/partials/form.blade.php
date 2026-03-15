@php
    $pictureDocument = $sell->exists ? $sell->documentFor('picture') : null;
    $singleDocumentTypes = collect($documentTypes)->except(['picture'])->all();
@endphp

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Customer and Sale Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $sell->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="father_name" class="form-label">Father's Name</label>
                <input type="text" id="father_name" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $sell->father_name) }}">
            </div>

            <div class="col-md-6">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="text" id="mobile_number" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $sell->mobile_number) }}">
            </div>

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
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $sell->address) }}</textarea>
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
