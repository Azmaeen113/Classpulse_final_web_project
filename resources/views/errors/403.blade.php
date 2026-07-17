@extends('layouts.guest')

@section('title', 'Access denied — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Access denied</h1>
    <p class="cp-muted mb-4">You don’t have permission for that page. Try your own dashboard instead.</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('home') }}" class="btn btn-cp">Home</a>
        @auth
            <a href="{{ route(auth()->user()->homeRouteName()) }}" class="btn btn-cp-outline">My dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-cp-outline">Log in</a>
        @endauth
    </div>
@endsection
