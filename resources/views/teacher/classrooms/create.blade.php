@extends('layouts.app')

@section('title', 'New classroom — ClassPulse')

@section('header')
    <h1 class="cp-page-title">New classroom</h1>
    <p class="cp-page-sub">A unique room code will be generated automatically.</p>
@endsection

@section('content')
    <div class="cp-surface-flat p-4" style="max-width: 640px;">
        <form method="POST" action="{{ route('teacher.classrooms.store') }}">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label for="subject" class="form-label">Subject (optional)</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}"
                       class="form-control @error('subject') is-invalid @enderror">
                @error('subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-cp">Create</button>
                <a href="{{ route('teacher.classrooms.index') }}" class="btn btn-cp-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
