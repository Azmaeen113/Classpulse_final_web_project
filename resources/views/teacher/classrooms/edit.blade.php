@extends('layouts.app')

@section('title', 'Edit classroom — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Edit classroom</h1>
    <p class="cp-page-sub">{{ $classroom->name }}</p>
@endsection

@section('content')
    <div class="cp-surface-flat p-4" style="max-width: 640px;">
        <form method="POST" action="{{ route('teacher.classrooms.update', $classroom) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $classroom->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject (optional)</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject', $classroom->subject) }}"
                       class="form-control @error('subject') is-invalid @enderror">
                @error('subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                       @checked(old('is_active', $classroom->is_active))>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-cp">Save</button>
                <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="btn btn-cp-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
