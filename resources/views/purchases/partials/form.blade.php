@php
    $existingPictures = $purchase->exists ? $purchase->pictureDocuments : collect();
    $existingCosts = $purchase->exists
        ? $purchase->modifyingCosts->map(fn ($cost) => [
            'reason' => $cost->reason,
            'cost' => number_format((float) $cost->cost, 2, '.', ''),
        ])->all()
        : [];

    $costRows = old('modifying_costs', $existingCosts);
    $selectedVehicleId = old('vehicle_id', request('vehicle_id', $purchase->vehicle_id));
    $selectedVehicle = $vehicles->firstWhere('id', (int) $selectedVehicleId);
    $selectedPaymentStatus = old('payment_status', $purchase->payment_status ?: 'unpaid');
    $selectedPaymentMethod = old('payment_method', $purchase->payment_method);

    if (empty($costRows)) {
        $costRows = [['reason' => '', 'cost' => '']];
    }
@endphp

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Owner and Purchase Information</h3>
    </div>
    <div class="card-body">
        @if ($vehicles->isEmpty())
            <div class="alert alert-warning">
                Add at least one vehicle before recording a purchase.
                <a href="{{ route('vehicles.create') }}" class="alert-link">Create vehicle</a>
            </div>
        @else
            <div class="alert alert-info">
                Each purchase increases stock by the quantity you enter for the selected vehicle.
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
                <div class="border rounded p-3 h-100 bg-light">
                    @if ($selectedVehicle)
                        <div class="fw-semibold">{{ $selectedVehicle->brand->name }} / {{ $selectedVehicle->category->name }}</div>
                        <div class="small text-muted">
                            Registration: {{ $selectedVehicle->registration_number ?: 'Not added' }} |
                            Engine: {{ $selectedVehicle->engine_number ?: 'Not added' }}
                        </div>
                        <div class="small text-muted mt-2">
                            Purchased: {{ $selectedVehicle->purchased_quantity }} |
                            Sold: {{ $selectedVehicle->sold_quantity }} |
                            Available: {{ $selectedVehicle->available_stock_quantity }}
                        </div>
                        <div class="small mt-2">
                            <span class="badge {{ $selectedVehicle->stock_badge_class }}">{{ $selectedVehicle->stock_status }}</span>
                        </div>
                    @else
                        <div class="text-muted">Select a vehicle to connect this purchase record.</div>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $purchase->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="father_name" class="form-label">Father's Name</label>
                <input type="text" id="father_name" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $purchase->father_name) }}">
            </div>

            <div class="col-md-4">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="text" id="mobile_number" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $purchase->mobile_number) }}">
            </div>

            <div class="col-md-2">
                <label for="quantity" class="form-label">Quantity</label>
                <input
                    type="number"
                    min="1"
                    step="1"
                    id="quantity"
                    name="quantity"
                    class="form-control @error('quantity') is-invalid @enderror"
                    value="{{ old('quantity', $purchase->quantity ?? 1) }}"
                    required
                >
            </div>

            <div class="col-md-3">
                <label for="purchasing_date" class="form-label">Purchasing Date</label>
                <input
                    type="date"
                    id="purchasing_date"
                    name="purchasing_date"
                    class="form-control @error('purchasing_date') is-invalid @enderror"
                    value="{{ old('purchasing_date', $purchase->purchasing_date?->format('Y-m-d')) }}"
                    required
                >
            </div>

            <div class="col-md-3">
                <label for="buying_price_from_owner" class="form-label">Buying Price from Owner</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="buying_price_from_owner"
                    name="buying_price_from_owner"
                    class="form-control @error('buying_price_from_owner') is-invalid @enderror"
                    value="{{ old('buying_price_from_owner', $purchase->buying_price_from_owner) }}"
                    required
                >
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
                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $purchase->address) }}</textarea>
            </div>

            <div class="col-12">
                <label for="payment_information" class="form-label">Payment Information</label>
                <textarea id="payment_information" name="payment_information" rows="3" class="form-control @error('payment_information') is-invalid @enderror" placeholder="Transaction id, account number, cheque details or other payment notes">{{ old('payment_information', $purchase->payment_information) }}</textarea>
            </div>

            <div class="col-12">
                <label for="extra_additional_note" class="form-label">Extra Additional Note</label>
                <textarea id="extra_additional_note" name="extra_additional_note" rows="4" class="form-control @error('extra_additional_note') is-invalid @enderror">{{ old('extra_additional_note', $purchase->extra_additional_note) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">Vehicle Pictures and Documents</h3>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <label for="pictures" class="form-label">Picture (Multi Pictures)</label>
            <input type="file" id="pictures" name="pictures[]" class="form-control @error('pictures.*') is-invalid @enderror" accept="image/*" multiple>
            <div class="form-text">Upload one or more vehicle photos.</div>
        </div>

        @if ($existingPictures->isNotEmpty())
            <div class="row g-3 mb-4">
                @foreach ($existingPictures as $picture)
                    <div class="col-md-3">
                        <div class="card h-100">
                            <img src="{{ $picture->url }}" alt="Purchase picture" class="card-img-top" style="height: 180px; object-fit: cover;">
                            <div class="card-body py-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_picture_ids[]" value="{{ $picture->id }}" id="remove_picture_{{ $picture->id }}">
                                    <label class="form-check-label small" for="remove_picture_{{ $picture->id }}">
                                        Remove picture
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="row g-3">
            @foreach ($singleDocumentTypes as $field => $label)
                @php
                    $document = $purchase->exists ? $purchase->documentFor($field) : null;
                @endphp

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
</div>

<div class="card card-outline card-warning">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Modifying Costs</h3>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-cost-row">
            <i class="bi bi-plus-circle me-1"></i>Add Cost
        </button>
    </div>
    <div class="card-body">
        <div id="cost-rows">
            @foreach ($costRows as $index => $costRow)
                <div class="row g-3 align-items-end cost-row mb-2">
                    <div class="col-md-7">
                        <label class="form-label">Reason</label>
                        <input type="text" name="modifying_costs[{{ $index }}][reason]" class="form-control" value="{{ $costRow['reason'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost</label>
                        <input type="number" min="0" step="0.01" name="modifying_costs[{{ $index }}][cost]" class="form-control" value="{{ $costRow['cost'] ?? '' }}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger remove-cost-row w-100">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const container = document.getElementById('cost-rows');
            const addButton = document.getElementById('add-cost-row');

            if (!container || !addButton) {
                return;
            }

            const reindexRows = () => {
                [...container.querySelectorAll('.cost-row')].forEach((row, index) => {
                    const reasonInput = row.querySelector('input[name*="[reason]"]');
                    const costInput = row.querySelector('input[name*="[cost]"]');

                    reasonInput.name = `modifying_costs[${index}][reason]`;
                    costInput.name = `modifying_costs[${index}][cost]`;
                });
            };

            const bindRemoveButtons = () => {
                container.querySelectorAll('.remove-cost-row').forEach((button) => {
                    button.onclick = () => {
                        if (container.querySelectorAll('.cost-row').length === 1) {
                            container.querySelector('input[name*="[reason]"]').value = '';
                            container.querySelector('input[name*="[cost]"]').value = '';
                            return;
                        }

                        button.closest('.cost-row').remove();
                        reindexRows();
                    };
                });
            };

            addButton.addEventListener('click', () => {
                const index = container.querySelectorAll('.cost-row').length;
                const row = document.createElement('div');
                row.className = 'row g-3 align-items-end cost-row mb-2';
                row.innerHTML = `
                    <div class="col-md-7">
                        <label class="form-label">Reason</label>
                        <input type="text" name="modifying_costs[${index}][reason]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cost</label>
                        <input type="number" min="0" step="0.01" name="modifying_costs[${index}][cost]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger remove-cost-row w-100">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `;

                container.appendChild(row);
                bindRemoveButtons();
            });

            bindRemoveButtons();
        })();
    </script>
@endpush

