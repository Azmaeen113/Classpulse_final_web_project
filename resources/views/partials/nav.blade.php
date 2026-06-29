@php
    $user = Auth::user();
    $role = $user->role ?? null;
@endphp

<nav class="navbar navbar-expand-lg cp-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand cp-brand" href="{{ Route::has('home') ? route('home') : url('/') }}">
            Class<span>Pulse</span>
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cpNav" aria-controls="cpNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="cpNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if ($role === 'teacher')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}" href="{{ route('teacher.dashboard') }}">
                            <i class="bi bi-house-door d-lg-none me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('teacher.classrooms.*') ? 'active' : '' }}" href="{{ route('teacher.classrooms.index') }}">Classrooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('teacher.quizzes.*') ? 'active' : '' }}" href="{{ route('teacher.quizzes.index') }}">Quizzes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('teacher.history') ? 'active' : '' }}" href="{{ route('teacher.history') }}">History</a>
                    </li>
                @elseif ($role === 'student')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('student.join') ? 'active' : '' }}" href="{{ route('student.join') }}">Join</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('student.history') ? 'active' : '' }}" href="{{ route('student.history') }}">History</a>
                    </li>
                @elseif ($role === 'admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}" href="{{ route('admin.activity.index') }}">Activity</a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    @php
                        $navDefault = in_array(Auth::user()->role ?? null, ['teacher', 'admin'], true) ? 'dark' : 'light';
                        $rawTheme = request()->cookie('cp_theme');
                        $theme = in_array($rawTheme, ['light', 'dark'], true) ? $rawTheme : $navDefault;
                        $nextTheme = $theme === 'light' ? 'dark' : 'light';
                    @endphp
                    {{-- Form POST always works even if JS fails; JS intercepts for instant flip --}}
                    <form method="POST" action="{{ route('preferences.theme') }}" class="d-inline m-0" data-theme-form>
                        @csrf
                        <input type="hidden" name="theme" value="{{ $nextTheme }}">
                        <button type="submit"
                            class="btn btn-sm btn-cp-outline"
                            data-theme-toggle
                            data-theme-url="{{ route('preferences.theme') }}"
                            aria-pressed="{{ $theme === 'light' ? 'true' : 'false' }}"
                            title="Switch to {{ $nextTheme }} mode">
                            <i class="bi {{ $theme === 'light' ? 'bi-sun-fill' : 'bi-moon-stars-fill' }}"></i>
                            <span data-theme-label>{{ $theme === 'light' ? 'Light' : 'Dark' }}</span>
                        </button>
                    </form>
                </li>

                @auth
                    <li class="nav-item dropdown"
                        data-notifications
                        data-list-url="{{ route('notifications.index') }}"
                        data-read-url="{{ url('/notifications/__ID__/read') }}"
                        data-read-all-url="{{ route('notifications.read-all') }}">
                        <a class="nav-link cp-bell-wrap" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="cp-bell-badge" data-notify-badge></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end cp-notify-menu p-0">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary border-opacity-25">
                                <span class="fw-semibold">Notifications</span>
                                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" data-mark-all-read>Mark all read</button>
                            </div>
                            <div data-notify-list>
                                <div class="dropdown-item-text cp-muted p-3">Loading...</div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $user->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('login') }}" target="_blank" rel="noopener">
                                    Open another account
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
