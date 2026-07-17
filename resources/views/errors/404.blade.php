@extends('layouts.guest')

@section('title', 'Page not found — ClassPulse')

@section('content')
    <h1 class="h3 mb-1">Page not found</h1>
    <p class="cp-muted mb-4">That link is missing or no longer available.</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('home') }}" class="btn btn-cp">Home</a>
        <a href="{{ route('login') }}" class="btn btn-cp-outline">Log in</a>
        @auth
            <a href="{{ route(auth()->user()->homeRouteName()) }}" class="btn btn-cp-outline">My dashboard</a>
        @endauth
    </div>
@endsection
