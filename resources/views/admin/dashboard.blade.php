@extends('layouts.app')

@section('title', 'Admin dashboard — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Admin dashboard</h1>
    <p class="cp-page-sub">Platform overview for ClassPulse.</p>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card label="Users" :value="$stats['users'] ?? 0" icon="bi-people" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Teachers" :value="$stats['teachers'] ?? 0" icon="bi-person-workspace" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Students" :value="$stats['students'] ?? 0" icon="bi-mortarboard" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Active sessions" :value="$stats['active_sessions'] ?? 0" icon="bi-broadcast" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="cp-surface-flat p-4">
                <div class="d-flex justify-content-between mb-3">
                    <h2 class="h5 mb-0">Recent users</h2>
                    @if (Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}" class="small">Manage</a>
                    @endif
                </div>
                @forelse ($recentUsers ?? [] as $user)
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="small cp-muted">{{ $user->email }}</div>
                        </div>
                        <span class="badge badge-cp text-uppercase">{{ $user->role }}</span>
                    </div>
                @empty
                    <p class="cp-muted mb-0">No users yet.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="cp-surface-flat p-4">
                <div class="d-flex justify-content-between mb-3">
                    <h2 class="h5 mb-0">Recent activity</h2>
                    @if (Route::has('admin.activity.index'))
                        <a href="{{ route('admin.activity.index') }}" class="small">View all</a>
                    @endif
                </div>
                @forelse ($recentActivity ?? [] as $log)
                    <div class="py-2 border-bottom border-secondary border-opacity-25">
                        <div class="fw-semibold">{{ $log->action }}</div>
                        <div class="small cp-muted">{{ $log->description }}</div>
                        <div class="small cp-muted">{{ optional($log->created_at)->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="cp-muted mb-0">No activity logged.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
