@extends('layouts.guest')

@section('title', 'Confirm password — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Confirm password</h1>
    <p class="cp-muted mb-4">This is a secure area. Please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-cp w-100">Confirm</button>
    </form>
@endsection
