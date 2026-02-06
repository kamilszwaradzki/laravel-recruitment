<section>
    <header class="mb-4">
        <h3 class="h5">{{ __('Update Password') }}</h3>
        <p class="text-muted small">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" type="password"
                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                   name="current_password" autocomplete="current-password">
            @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
            <input id="update_password_password" type="password"
                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                   name="password" autocomplete="new-password">
            @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" type="password"
                   class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                   name="password_confirmation" autocomplete="new-password">
            @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-dark">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <p class="small text-muted mb-0">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
