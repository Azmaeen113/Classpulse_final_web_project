@extends('layouts.app')

@section('title', 'Add question — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Add question</h1>
    <p class="cp-page-sub">{{ $quiz->title }}</p>
@endsection

@section('content')
    <div class="cp-surface-flat p-4" style="max-width: 800px;">
        <form method="POST" action="{{ route('teacher.questions.store', $quiz) }}" enctype="multipart/form-data">
            @csrf
            @include('teacher.questions._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-cp">Save question</button>
                <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="btn btn-cp-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
