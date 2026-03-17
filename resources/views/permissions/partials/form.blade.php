<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Permission Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Permission Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $permission->name) }}" required>
            </div>

            <div class="col-md-6">
                <label for="guard_name_view" class="form-label">Guard</label>
                <input type="text" id="guard_name_view" class="form-control" value="web" disabled>
                <input type="hidden" name="guard_name" value="web">
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>
