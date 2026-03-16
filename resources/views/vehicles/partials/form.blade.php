@php
    $canSaveVehicle = $brands->isNotEmpty() && $categories->isNotEmpty();
@endphp

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Vehicle Master Information</h3>
    </div>
    <div class="card-body">
        @unless ($canSaveVehicle)
            <div class="alert alert-warning">
                Create at least one brand and one category before saving a vehicle.
                <a href="{{ route('brands.create') }}" class="alert-link">Add brand</a> |
                <a href="{{ route('categories.create') }}" class="alert-link">Add category</a>
            </div>
        @endunless

        <div class="row g-3">
            <div class="col-md-6">
                <label for="brand_id" class="form-label">Brand</label>
                <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror" required>
                    <option value="">Select brand</option>
                    @foreach ($brands as $brandOption)
                        <option value="{{ $brandOption->id }}" @selected((string) old('brand_id', $vehicle->brand_id) === (string) $brandOption->id)>
                            {{ $brandOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="category_id" class="form-label">Category</label>
                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected((string) old('category_id', $vehicle->category_id) === (string) $categoryOption->id)>
                            {{ $categoryOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label for="name" class="form-label">Vehicle / Product Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $vehicle->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="code" class="form-label">Stock Code</label>
                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $vehicle->code) }}">
            </div>

            <div class="col-md-4">
                <label for="model" class="form-label">Model</label>
                <input type="text" id="model" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $vehicle->model) }}">
            </div>

            <div class="col-md-4">
                <label for="year" class="form-label">Year</label>
                <input type="number" id="year" name="year" min="1900" max="2100" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', $vehicle->year) }}">
            </div>

            <div class="col-md-4">
                <label for="color" class="form-label">Color</label>
                <input type="text" id="color" name="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color', $vehicle->color) }}">
            </div>

            <div class="col-md-4">
                <label for="registration_number" class="form-label">Registration Number</label>
                <input type="text" id="registration_number" name="registration_number" class="form-control @error('registration_number') is-invalid @enderror" value="{{ old('registration_number', $vehicle->registration_number) }}">
            </div>

            <div class="col-md-4">
                <label for="engine_number" class="form-label">Engine Number</label>
                <input type="text" id="engine_number" name="engine_number" class="form-control @error('engine_number') is-invalid @enderror" value="{{ old('engine_number', $vehicle->engine_number) }}">
            </div>

            <div class="col-md-4">
                <label for="chassis_number" class="form-label">Chassis Number</label>
                <input type="text" id="chassis_number" name="chassis_number" class="form-control @error('chassis_number') is-invalid @enderror" value="{{ old('chassis_number', $vehicle->chassis_number) }}">
            </div>

            <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $vehicle->notes) }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" @disabled(! $canSaveVehicle)>
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>
