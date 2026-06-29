@php
    $zone = 'zone-guest';
    $zoneKey = 'guest';
    if (auth()->check()) {
        $zoneKey = match (auth()->user()->role) {
            'student' => 'student',
            'teacher' => 'teacher',
            'admin' => 'admin',
            default => 'guest',
        };
        $zone = match ($zoneKey) {
            'student' => 'zone-student',
            'teacher', 'admin' => 'zone-teacher',
            default => 'zone-guest',
        };
    }
    // Students/guests default bright; teachers/admins default dark
    $defaultTheme = in_array($zoneKey, ['teacher', 'admin'], true) ? 'dark' : 'light';
    $rawTheme = request()->cookie('cp_theme');
    $theme = in_array($rawTheme, ['light', 'dark'], true) ? $rawTheme : $defaultTheme;
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}" data-zone="{{ $zoneKey }}" class="{{ $themeClass }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ClassPulse'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/classpulse.css') }}?v={{ filemtime(public_path('css/classpulse.css')) }}" rel="stylesheet">
    {{-- Sync classes from plain cp_theme cookie only; ignore encrypted leftovers --}}
    <script>
        (function () {
            try {
                var m = document.cookie.match(/(?:^|; )cp_theme=([^;]*)/);
                var raw = m ? decodeURIComponent(m[1]) : null;
                var t = (raw === 'light' || raw === 'dark')
                    ? raw
                    : (document.documentElement.getAttribute('data-theme') || 'dark');
                t = (t === 'light') ? 'light' : 'dark';
                var on = t === 'light' ? 'theme-light' : 'theme-dark';
                var off = t === 'light' ? 'theme-dark' : 'theme-light';
                document.documentElement.setAttribute('data-theme', t);
                document.documentElement.classList.remove(off);
                document.documentElement.classList.add(on);
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="cp-bg {{ $zone }} {{ $themeClass }}" data-theme="{{ $theme }}">
    @include('partials.nav')

    <main class="container py-4">
        @include('partials.flash')

        @isset($header)
            <div class="mb-4">
                {{ $header }}
            </div>
        @elseif(View::hasSection('header'))
            <div class="mb-4">
                @yield('header')
            </div>
        @endif

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/classpulse-poll.js') }}?v={{ filemtime(public_path('js/classpulse-poll.js')) }}"></script>
    <script src="{{ asset('js/theme.js') }}?v={{ filemtime(public_path('js/theme.js')) }}"></script>
    <script src="{{ asset('js/notifications.js') }}?v={{ filemtime(public_path('js/notifications.js')) }}"></script>
    @stack('scripts')
</body>
</html>
