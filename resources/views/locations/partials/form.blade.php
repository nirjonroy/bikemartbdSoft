<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Location Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Location Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $location->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="code" class="form-label">Location Code</label>
                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $location->code) }}" required>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $location->email) }}">
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $location->phone) }}">
            </div>

            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $location->address) }}</textarea>
            </div>

            <div class="col-md-6">
                <label for="is_active" class="form-label">Status</label>
                <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                    <option value="1" @selected((string) old('is_active', $location->is_active ?? true) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', $location->is_active ?? true) === '0')>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $location->notes) }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>
