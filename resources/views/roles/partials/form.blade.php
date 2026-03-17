<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Role Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Role Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
            </div>
        </div>

        <hr>

        <div>
            <label class="form-label d-block">Permissions</label>
            <div class="row g-3">
                @forelse ($permissions as $permission)
                    <div class="col-md-4">
                        <div class="form-check border rounded p-3 h-100">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->name }}"
                                id="permission_{{ $permission->id }}"
                                @checked(in_array($permission->name, $selectedPermissions, true))
                            >
                            <label class="form-check-label ms-1" for="permission_{{ $permission->id }}">
                                {{ $permission->name }}
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">No permissions available yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy me-1"></i>{{ $submitLabel }}
        </button>
    </div>
</div>
