@extends('layouts.guest')

@section('title', 'Forgot password — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Forgot password</h1>
    <p class="cp-muted mb-4">Enter your email and we will send a reset link.</p>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-cp w-100">Email reset link</button>
    </form>

    <p class="text-center mt-4 mb-0">
        <a href="{{ route('login') }}">Back to login</a>
    </p>
@endsection
