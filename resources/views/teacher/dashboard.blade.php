@extends('layouts.app')

@section('title', 'Teacher dashboard — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Teacher dashboard</h1>
    <p class="cp-page-sub">Welcome back, {{ Auth::user()->name }}.</p>
@endsection

@section('content')
    @if (!empty($activeSessions) && count($activeSessions))
        <div class="cp-live-banner d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="cp-live-dot"></span>
                    <span class="badge badge-live">LIVE</span>
                </div>
                <div class="fw-semibold">{{ $activeSessions[0]->quiz->title ?? 'Active session' }}</div>
            </div>
            <a href="{{ route('teacher.live.show', $activeSessions[0]) }}" class="btn btn-cp-live">Open control room</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-stat-card label="Classrooms" :value="isset($classrooms) ? $classrooms->count() : 0" icon="bi-people" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Quizzes" :value="isset($quizzes) ? $quizzes->count() : 0" icon="bi-journal-richtext" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Live sessions" :value="isset($activeSessions) ? count($activeSessions) : 0" icon="bi-broadcast" />
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('teacher.classrooms.create') }}" class="btn btn-cp"><i class="bi bi-plus-lg"></i> New classroom</a>
        <a href="{{ route('teacher.quizzes.create') }}" class="btn btn-cp-outline"><i class="bi bi-plus-lg"></i> New quiz</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="cp-surface-flat p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Recent classrooms</h2>
                    <a href="{{ route('teacher.classrooms.index') }}" class="small">View all</a>
                </div>
                @forelse ($classrooms ?? [] as $classroom)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                        <div>
                            <div class="fw-semibold">{{ $classroom->name }}</div>
                            <div class="small cp-muted">{{ $classroom->subject ?? 'No subject' }} · {{ $classroom->room_code }}</div>
                        </div>
                        <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="btn btn-sm btn-cp-outline">Open</a>
                    </div>
                @empty
                    <x-empty-state title="No classrooms yet" message="Create a classroom to get a room code." icon="bi-people" />
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="cp-surface-flat p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Recent quizzes</h2>
                    <a href="{{ route('teacher.quizzes.index') }}" class="small">View all</a>
                </div>
                @forelse ($quizzes ?? [] as $quiz)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                        <div>
                            <div class="fw-semibold">{{ $quiz->title }}</div>
                            <div class="small cp-muted">{{ $quiz->classroom->name ?? 'Classroom' }}</div>
                        </div>
                        <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="btn btn-sm btn-cp-outline">Open</a>
                    </div>
                @empty
                    <x-empty-state title="No quizzes yet" message="Build a quiz and go live." icon="bi-journal-richtext" />
                @endforelse
            </div>
        </div>
    </div>
@endsection
