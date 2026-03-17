<div class="card card-outline card-danger">
    <div class="card-header">
        <h3 class="card-title">Delete Account</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">This action is permanent.</div>
            <div>Once your account is deleted, all related access and data will be removed permanently.</div>
        </div>

        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="delete_password" class="form-label">Confirm with Password</label>
                    <input
                        id="delete_password"
                        name="password"
                        type="password"
                        class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                        placeholder="Enter your current password"
                    >
                    @if ($errors->userDeletion->has('password'))
                        <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                    @endif
                </div>

                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account?');">
                        <i class="bi bi-trash me-1"></i>Delete Account
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
