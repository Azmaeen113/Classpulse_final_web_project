@extends('layouts.app')

@section('title', 'Quizzes — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="cp-page-title">Quizzes</h1>
            <p class="cp-page-sub mb-0">Build question sets and start live sessions.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teacher.dashboard') }}" class="btn btn-cp-outline">
                <i class="bi bi-house-door me-1"></i> Home
            </a>
            @if (Route::has('teacher.quizzes.create'))
                <a href="{{ route('teacher.quizzes.create') }}" class="btn btn-cp"><i class="bi bi-plus-lg"></i> New quiz</a>
            @endif
        </div>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search quizzes...">
        </div>
        <div class="col-md-4">
            <button class="btn btn-cp-outline w-100" type="submit">Search</button>
        </div>
    </form>

    @if (($quizzes ?? collect())->isEmpty())
        <x-empty-state title="No quizzes" message="Create a quiz for one of your classrooms.">
            @if (Route::has('teacher.quizzes.create'))
                <a href="{{ route('teacher.quizzes.create') }}" class="btn btn-cp">Create quiz</a>
            @endif
        </x-empty-state>
    @else
        <div class="cp-surface-flat table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Classroom</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quizzes as $quiz)
                        <tr>
                            <td class="fw-semibold">{{ $quiz->title }}</td>
                            <td class="cp-muted">{{ $quiz->classroom->name ?? '—' }}</td>
                            <td>{{ $quiz->questions_count ?? $quiz->questions->count() ?? 0 }}</td>
                            <td>
                                @if ($quiz->is_published)
                                    <span class="badge badge-live">Published</span>
                                @else
                                    <span class="badge text-bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (Route::has('teacher.quizzes.show'))
                                    <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="btn btn-sm btn-cp-outline">Open</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($quizzes, 'withQueryString'))
            <div class="mt-3">
                {{ $quizzes->withQueryString()->links() }}
            </div>
        @endif
    @endif
@endsection
