@extends('layouts.app')

@section('title', 'Profile — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Profile</h1>
    <p class="cp-page-sub">Update your account details and password.</p>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="cp-surface-flat p-4">
                <h2 class="h5 mb-3">Profile information</h2>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" name="name" type="text"
                               value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email"
                               value="{{ old('email', $user->email) }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-cp">Save</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="cp-surface-flat p-4 mb-4">
                <h2 class="h5 mb-3">Update password</h2>
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input id="current_password" name="current_password" type="password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="form-control" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-cp">Update password</button>
                </form>
            </div>

            <div class="cp-surface-flat p-4 border border-danger border-opacity-25">
                <h2 class="h5 mb-2 text-danger">Delete account</h2>
                <p class="cp-muted">Once deleted, your account and related data cannot be recovered.</p>
                <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Delete your account permanently?');">
                    @csrf
                    @method('delete')
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Password</label>
                        <input id="delete_password" name="password" type="password"
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                               required autocomplete="current-password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-cp-danger">Delete account</button>
                </form>
            </div>
        </div>
    </div>
@endsection
