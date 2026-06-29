<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('cp_theme', 'light') }}" data-zone="guest">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ClassPulse — Live classroom quizzes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/classpulse.css') }}" rel="stylesheet">
</head>
<body class="cp-bg">
    <nav class="navbar navbar-expand-lg cp-navbar">
        <div class="container">
            <a class="navbar-brand cp-brand" href="{{ url('/') }}">Class<span>Pulse</span></a>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-cp">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-cp-outline">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-cp">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="cp-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <h1 class="cp-hero-title cp-fade-in">Class<span class="accent">Pulse</span></h1>
                    <p class="cp-hero-lead mb-4">
                        Launch live quizzes, watch answers stream in, and project leaderboards that keep every student locked in.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-cp btn-lg">Open dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-cp btn-lg">Get started</a>
                            <a href="{{ route('login') }}" class="btn btn-cp-outline btn-lg">Log in</a>
                        @endauth
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="cp-surface p-4 p-md-5 cp-fade-in">
                        <div class="cp-muted text-uppercase small mb-2">Built for class</div>
                        <h2 class="h4 fw-bold mb-3">Live quizzes that stay in sync</h2>
                        <ul class="list-unstyled mb-0 cp-muted">
                            <li class="mb-2"><i class="bi bi-check2-circle me-2 text-info"></i>Teacher control room</li>
                            <li class="mb-2"><i class="bi bi-check2-circle me-2 text-info"></i>Room codes and QR join</li>
                            <li class="mb-0"><i class="bi bi-check2-circle me-2 text-info"></i>Projector leaderboard</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="cp-surface-flat p-4 h-100">
                        <i class="bi bi-broadcast text-info fs-3"></i>
                        <h2 class="h5 mt-3">Live control room</h2>
                        <p class="cp-muted mb-0">Pause, reveal, and advance questions while watching response bars update in real time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="cp-surface-flat p-4 h-100">
                        <i class="bi bi-qr-code text-info fs-3"></i>
                        <h2 class="h5 mt-3">Room codes and QR</h2>
                        <p class="cp-muted mb-0">Students join with a six-character code or scan a classroom QR.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="cp-surface-flat p-4 h-100">
                        <i class="bi bi-trophy text-info fs-3"></i>
                        <h2 class="h5 mt-3">Projector leaderboard</h2>
                        <p class="cp-muted mb-0">Large-format ranks and timers built for the classroom display.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
</body>
</html>
