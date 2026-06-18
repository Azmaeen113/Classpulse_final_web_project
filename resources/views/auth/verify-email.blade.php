@extends('layouts.guest')

@section('title', 'Verify email — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Verify email</h1>
    <p class="cp-muted mb-4">
        Thanks for signing up. Please verify your email by clicking the link we sent you.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-cp w-100">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-cp-outline w-100">Log out</button>
    </form>
@endsection
