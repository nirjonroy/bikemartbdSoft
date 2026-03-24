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
    $canQuickAddVehicle = auth()->user()?->can('manage vehicles') ?? false;
    $canCreateVehicleFromModal = $canQuickAddVehicle && $brands->isNotEmpty() && $categories->isNotEmpty();
    $showStockInformation = $businessSetting->show_stock_information ?? true;
    $showQuantityFields = $businessSetting->show_quantity_fields ?? true;
    $vehicleOptionsData = $vehicles
        ->map(fn ($vehicleOption) => [
            'id' => (int) $vehicleOption->id,
            'label' => $vehicleOption->display_name . ' | ' . $vehicleOption->brand->name . ' / ' . $vehicleOption->category->name,
            'search' => strtolower(implode(' ', array_filter([
                $vehicleOption->name,
                $vehicleOption->code,
                $vehicleOption->model,
                $vehicleOption->registration_number,
                $vehicleOption->engine_number,
                $vehicleOption->chassis_number,
                $vehicleOption->color,
                $vehicleOption->brand->name,
                $vehicleOption->category->name,
            ]))),
            'brand_name' => $vehicleOption->brand->name,
            'category_name' => $vehicleOption->category->name,
            'registration_number' => $vehicleOption->registration_number ?: 'Not added',
            'engine_number' => $vehicleOption->engine_number ?: 'Not added',
            'purchased_quantity' => (int) $vehicleOption->purchased_quantity,
            'sold_quantity' => (int) $vehicleOption->sold_quantity,
            'available_stock_quantity' => (int) $vehicleOption->available_stock_quantity,
            'stock_status' => $vehicleOption->stock_status,
            'stock_badge_class' => $vehicleOption->stock_badge_class,
        ])
        ->values()
        ->all();

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
                No vehicle is available for purchase yet.
                @if ($canCreateVehicleFromModal)
                    Use the <strong>Add Vehicle</strong> button below to create one without leaving this page.
                @elseif ($canQuickAddVehicle)
                    Create at least one brand and category first, then you can add a vehicle from this page.
                @else
                    Ask an administrator to create a vehicle before recording a purchase.
                @endif
            </div>
        @else
            <div class="alert alert-info">
                {{ $showStockInformation ? ($showQuantityFields ? 'Each purchase increases stock by the quantity you enter for the selected vehicle.' : 'Each purchase updates inventory automatically for the selected vehicle.') : ($showQuantityFields ? 'Enter the purchase quantity for the selected vehicle.' : 'Record the purchase for the selected vehicle.') }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <label for="vehicle_id" class="form-label mb-0">Vehicle / Product</label>
                    @if ($canQuickAddVehicle)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#quickVehicleModal"
                            @disabled(! $canCreateVehicleFromModal)
                        >
                            <i class="bi bi-plus-circle me-1"></i>Add Vehicle
                        </button>
                    @endif
                </div>

                <input
                    type="search"
                    id="vehicle_search"
                    class="form-control mb-2"
                    placeholder="Search by vehicle, code, registration, engine, brand or category"
                    autocomplete="off"
                >

                <select id="vehicle_id" name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                    <option value="">Select a vehicle</option>
                    @foreach ($vehicles as $vehicleOption)
                        <option value="{{ $vehicleOption->id }}" @selected((string) $selectedVehicleId === (string) $vehicleOption->id)>
                            {{ $vehicleOption->display_name }} | {{ $vehicleOption->brand->name }} / {{ $vehicleOption->category->name }}
                        </option>
                    @endforeach
                </select>
                @error('vehicle_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @if ($canQuickAddVehicle && ! $canCreateVehicleFromModal)
                    <div class="form-text text-danger">Add at least one brand and category to use quick vehicle creation.</div>
                @endif
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
                        value="{{ old('quantity', $purchase->quantity ?? 1) }}"
                        required
                    >
                </div>
            @else
                <input type="hidden" id="quantity" name="quantity" value="{{ old('quantity', $purchase->quantity ?? 1) }}">
            @endif

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

@if ($canQuickAddVehicle)
    <div class="modal fade" id="quickVehicleModal" tabindex="-1" aria-labelledby="quickVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickVehicleModalLabel">Add Vehicle / Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quick-vehicle-feedback" class="alert d-none mb-3"></div>

                    @if (! $canCreateVehicleFromModal)
                        <div class="alert alert-warning mb-0">
                            You need at least one brand and one category before adding a vehicle from this page.
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="quick_vehicle_brand_id" class="form-label">Brand</label>
                                <select id="quick_vehicle_brand_id" class="form-select">
                                    <option value="">Select brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-error-for="brand_id"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_category_id" class="form-label">Category</label>
                                <select id="quick_vehicle_category_id" class="form-select">
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-error-for="category_id"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_name" class="form-label">Vehicle / Product Name</label>
                                <input type="text" id="quick_vehicle_name" class="form-control">
                                <div class="invalid-feedback" data-error-for="name"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_code" class="form-label">Code</label>
                                <input type="text" id="quick_vehicle_code" class="form-control">
                                <div class="invalid-feedback" data-error-for="code"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_model" class="form-label">Model</label>
                                <input type="text" id="quick_vehicle_model" class="form-control">
                                <div class="invalid-feedback" data-error-for="model"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_year" class="form-label">Year</label>
                                <input type="number" id="quick_vehicle_year" class="form-control" min="1900" max="2100">
                                <div class="invalid-feedback" data-error-for="year"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_color" class="form-label">Color</label>
                                <input type="text" id="quick_vehicle_color" class="form-control">
                                <div class="invalid-feedback" data-error-for="color"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_registration_number" class="form-label">Registration Number</label>
                                <input type="text" id="quick_vehicle_registration_number" class="form-control">
                                <div class="invalid-feedback" data-error-for="registration_number"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_engine_number" class="form-label">Engine Number</label>
                                <input type="text" id="quick_vehicle_engine_number" class="form-control">
                                <div class="invalid-feedback" data-error-for="engine_number"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="quick_vehicle_chassis_number" class="form-label">Chassis Number</label>
                                <input type="text" id="quick_vehicle_chassis_number" class="form-control">
                                <div class="invalid-feedback" data-error-for="chassis_number"></div>
                            </div>

                            <div class="col-12">
                                <label for="quick_vehicle_notes" class="form-label">Notes</label>
                                <textarea id="quick_vehicle_notes" class="form-control" rows="3"></textarea>
                                <div class="invalid-feedback" data-error-for="notes"></div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="quick-vehicle-save" @disabled(! $canCreateVehicleFromModal)>
                        <i class="bi bi-floppy me-1"></i>Save Vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
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

        (() => {
            const vehicleSelect = document.getElementById('vehicle_id');
            const vehicleSearch = document.getElementById('vehicle_search');
            const vehicleDetails = document.getElementById('selected-vehicle-details');
            const quickVehicleModalElement = document.getElementById('quickVehicleModal');
            const quickVehicleSaveButton = document.getElementById('quick-vehicle-save');
            const quickVehicleFeedback = document.getElementById('quick-vehicle-feedback');
            const quickStoreUrl = @json($canQuickAddVehicle ? route('vehicles.quick-store') : null);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const allVehicles = @json($vehicleOptionsData);
            const vehicleMap = new Map(allVehicles.map((vehicle) => [String(vehicle.id), vehicle]));
            const showStockInformation = @json($showStockInformation);
            const quickVehicleModal = quickVehicleModalElement && window.bootstrap
                ? new window.bootstrap.Modal(quickVehicleModalElement)
                : null;

            if (!vehicleSelect || !vehicleSearch || !vehicleDetails) {
                return;
            }

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            };

            const currentVehicleId = () => String(vehicleSelect.value || '');

            const buildSearchText = (vehicle) => [
                vehicle.label,
                vehicle.brand_name,
                vehicle.category_name,
                vehicle.registration_number,
                vehicle.engine_number,
            ].filter(Boolean).join(' ').toLowerCase();

            const vehicleDetailsMarkup = (vehicle) => {
                if (!vehicle) {
                    return '<div class="text-muted">Select a vehicle to connect this purchase record.</div>';
                }

                return `
                    <div class="fw-semibold">${escapeHtml(vehicle.brand_name)} / ${escapeHtml(vehicle.category_name)}</div>
                    <div class="small text-muted">
                        Registration: ${escapeHtml(vehicle.registration_number)} |
                        Engine: ${escapeHtml(vehicle.engine_number)}
                    </div>
                    ${showStockInformation ? `
                        <div class="small text-muted mt-2">
                            Purchased: ${escapeHtml(String(vehicle.purchased_quantity ?? 0))} |
                            Sold: ${escapeHtml(String(vehicle.sold_quantity ?? 0))} |
                            Available: ${escapeHtml(String(vehicle.available_stock_quantity ?? 0))}
                        </div>
                        <div class="small mt-2">
                            <span class="badge ${vehicle.stock_badge_class}">${escapeHtml(vehicle.stock_status)}</span>
                        </div>
                    ` : ''}
                `;
            };

            const renderVehicleDetails = () => {
                vehicleDetails.innerHTML = vehicleDetailsMarkup(vehicleMap.get(currentVehicleId()) || null);
            };

            const filteredVehicles = (query) => {
                const normalizedQuery = query.trim().toLowerCase();
                let results = normalizedQuery === ''
                    ? [...allVehicles]
                    : allVehicles.filter((vehicle) => (vehicle.search || buildSearchText(vehicle)).includes(normalizedQuery));

                const selectedId = currentVehicleId();

                if (selectedId !== '' && !results.some((vehicle) => String(vehicle.id) === selectedId)) {
                    const selectedVehicle = vehicleMap.get(selectedId);

                    if (selectedVehicle) {
                        results = [selectedVehicle, ...results];
                    }
                }

                return results;
            };

            const renderVehicleOptions = (query = '') => {
                const selectedId = currentVehicleId();
                const results = filteredVehicles(query);

                vehicleSelect.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = results.length > 0 ? 'Select a vehicle' : 'No vehicles match this search';
                vehicleSelect.appendChild(placeholderOption);

                results.forEach((vehicle) => {
                    const option = document.createElement('option');
                    option.value = String(vehicle.id);
                    option.textContent = vehicle.label;
                    option.selected = selectedId !== '' && selectedId === String(vehicle.id);
                    vehicleSelect.appendChild(option);
                });

                if (selectedId !== '' && !results.some((vehicle) => String(vehicle.id) === selectedId)) {
                    vehicleSelect.value = '';
                }
            };

            vehicleSearch.addEventListener('input', (event) => {
                renderVehicleOptions(event.target.value);
                renderVehicleDetails();
            });

            vehicleSelect.addEventListener('change', () => {
                renderVehicleDetails();
            });

            renderVehicleOptions();
            renderVehicleDetails();

            if (!quickVehicleModalElement || !quickVehicleSaveButton || !quickStoreUrl || !csrfToken) {
                return;
            }

            const quickVehicleFields = {
                brand_id: document.getElementById('quick_vehicle_brand_id'),
                category_id: document.getElementById('quick_vehicle_category_id'),
                name: document.getElementById('quick_vehicle_name'),
                code: document.getElementById('quick_vehicle_code'),
                model: document.getElementById('quick_vehicle_model'),
                year: document.getElementById('quick_vehicle_year'),
                color: document.getElementById('quick_vehicle_color'),
                registration_number: document.getElementById('quick_vehicle_registration_number'),
                engine_number: document.getElementById('quick_vehicle_engine_number'),
                chassis_number: document.getElementById('quick_vehicle_chassis_number'),
                notes: document.getElementById('quick_vehicle_notes'),
            };

            const fieldErrorElement = (fieldName) => quickVehicleModalElement.querySelector(`[data-error-for="${fieldName}"]`);

            const resetQuickVehicleValidation = () => {
                Object.entries(quickVehicleFields).forEach(([fieldName, element]) => {
                    if (!element) {
                        return;
                    }

                    element.classList.remove('is-invalid');
                    const errorElement = fieldErrorElement(fieldName);

                    if (errorElement) {
                        errorElement.textContent = '';
                    }
                });

                if (quickVehicleFeedback) {
                    quickVehicleFeedback.className = 'alert d-none mb-3';
                    quickVehicleFeedback.textContent = '';
                }
            };

            const resetQuickVehicleForm = () => {
                Object.values(quickVehicleFields).forEach((element) => {
                    if (!element) {
                        return;
                    }

                    element.value = '';
                });

                resetQuickVehicleValidation();
            };

            const setQuickVehicleError = (fieldName, message) => {
                const element = quickVehicleFields[fieldName];
                const errorElement = fieldErrorElement(fieldName);

                if (element) {
                    element.classList.add('is-invalid');
                }

                if (errorElement) {
                    errorElement.textContent = message;
                }
            };

            quickVehicleModalElement.addEventListener('hidden.bs.modal', resetQuickVehicleForm);

            quickVehicleSaveButton.addEventListener('click', async () => {
                quickVehicleSaveButton.disabled = true;
                resetQuickVehicleValidation();

                const payload = Object.fromEntries(
                    Object.entries(quickVehicleFields).map(([fieldName, element]) => [fieldName, element?.value ?? ''])
                );

                try {
                    const response = await fetch(quickStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            Object.entries(data.errors).forEach(([fieldName, messages]) => {
                                setQuickVehicleError(fieldName, Array.isArray(messages) ? messages[0] : messages);
                            });

                            if (quickVehicleFeedback) {
                                quickVehicleFeedback.className = 'alert alert-danger mb-3';
                                quickVehicleFeedback.textContent = 'Please fix the highlighted vehicle fields and try again.';
                            }

                            return;
                        }

                        throw new Error(data.message || 'Unable to save the vehicle right now.');
                    }

                    const newVehicle = data.vehicle;
                    newVehicle.search = buildSearchText(newVehicle);
                    allVehicles.unshift(newVehicle);
                    vehicleMap.set(String(newVehicle.id), newVehicle);
                    vehicleSearch.value = '';
                    renderVehicleOptions();
                    vehicleSelect.value = String(newVehicle.id);
                    vehicleSelect.classList.remove('is-invalid');
                    renderVehicleDetails();
                    quickVehicleModal?.hide();
                } catch (error) {
                    if (quickVehicleFeedback) {
                        quickVehicleFeedback.className = 'alert alert-danger mb-3';
                        quickVehicleFeedback.textContent = error.message || 'Unable to save the vehicle right now.';
                    }
                } finally {
                    quickVehicleSaveButton.disabled = false;
                }
            });
        })();
    </script>
@endpush