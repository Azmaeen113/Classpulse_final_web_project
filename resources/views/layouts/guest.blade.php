<!DOCTYPE html>
@php
    $rawTheme = request()->cookie('cp_theme');
    $theme = in_array($rawTheme, ['light', 'dark'], true) ? $rawTheme : 'light';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}" data-zone="guest" class="{{ $themeClass }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ClassPulse'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/classpulse.css') }}?v={{ filemtime(public_path('css/classpulse.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="cp-bg zone-guest {{ $themeClass }}" data-theme="{{ $theme }}">
    <div class="cp-auth-wrap">
        <div class="w-100" style="max-width: 480px;">
            <div class="text-center mb-4">
                <a href="{{ Route::has('home') ? route('home') : url('/') }}" class="cp-brand fs-2 text-decoration-none">
                    Class<span>Pulse</span>
                </a>
            </div>
            <div class="cp-surface cp-auth-card mx-auto">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/classpulse-poll.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
    @stack('scripts')
</body>
</html>
