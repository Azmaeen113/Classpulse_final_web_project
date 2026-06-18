@extends('layouts.guest')

@section('title', 'Log in — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Welcome back</h1>
    <p class="cp-muted mb-2">Sign in to your ClassPulse account.</p>
    <p class="small cp-muted mb-2">
        Tip: open this page in another tab to stay logged in as teacher and student at the same time.
    </p>
    <p class="small mb-4">
        <a href="{{ route('auth.reset-browser') }}">Page keeps redirecting? Clear session and try again</a>
    </p>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-cp w-100">Log in</button>
    </form>

    <p class="text-center cp-muted mt-4 mb-0">
        Need an account?
        <a href="{{ route('register') }}">Register</a>
    </p>
@endsection
