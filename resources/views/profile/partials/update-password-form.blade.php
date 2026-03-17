<div class="card card-outline card-success mb-4">
    <div class="card-header">
        <h3 class="card-title">Update Password</h3>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">Ensure your account is using a strong password to stay secure.</p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                        autocomplete="current-password"
                    >
                    @if ($errors->updatePassword->has('current_password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label for="password" class="form-label">New Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                        autocomplete="new-password"
                    >
                    @if ($errors->updatePassword->has('password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                        autocomplete="new-password"
                    >
                    @if ($errors->updatePassword->has('password_confirmation'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                    @endif
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-shield-lock me-1"></i>Update Password
                </button>
            </div>
        </form>
    </div>
</div>
