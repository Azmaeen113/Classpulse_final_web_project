@extends('layouts.app')

@section('title', 'New quiz — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="cp-page-title">New quiz</h1>
            <p class="cp-page-sub mb-0">Attach the quiz to a classroom, then add questions.</p>
        </div>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-cp-outline">
            <i class="bi bi-house-door me-1"></i> Home
        </a>
    </div>
@endsection

@section('content')
    <div class="cp-surface-flat p-4" style="max-width: 720px;">
        <form method="POST" action="{{ route('teacher.quizzes.store') }}">
            @csrf
            <div class="mb-3">
                <label for="classroom_id" class="form-label">Classroom</label>
                <select id="classroom_id" name="classroom_id" class="form-select @error('classroom_id') is-invalid @enderror" required>
                    <option value="" disabled {{ old('classroom_id', request('classroom_id')) ? '' : 'selected' }}>Select classroom</option>
                    @foreach ($classrooms ?? [] as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', request('classroom_id')) === (string) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
                @error('classroom_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (optional)</label>
                <textarea id="description" name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="default_time_limit_seconds" class="form-label">Default question time (seconds)</label>
                <input id="default_time_limit_seconds" type="number" min="5" max="600" name="default_time_limit_seconds"
                       value="{{ old('default_time_limit_seconds', 30) }}"
                       class="form-control @error('default_time_limit_seconds') is-invalid @enderror" required>
                @error('default_time_limit_seconds')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="small cp-muted mb-0 mt-1">Used for new manual and AI questions. You can still set a different time on each question.</p>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" @checked(old('is_published'))>
                <label class="form-check-label" for="is_published">Published</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-cp">Create</button>
                <a href="{{ route('teacher.quizzes.index') }}" class="btn btn-cp-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
