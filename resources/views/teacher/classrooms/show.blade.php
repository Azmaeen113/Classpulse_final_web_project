@extends('layouts.app')

@section('title', $classroom->name . ' — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="cp-page-title">{{ $classroom->name }}</h1>
            <p class="cp-page-sub mb-0">{{ $classroom->subject ?? 'No subject' }}</p>
        </div>
        <div class="d-flex gap-2">
            @if (Route::has('teacher.classrooms.edit'))
                <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="btn btn-cp-outline">Edit</a>
            @endif
            @if (Route::has('teacher.quizzes.create'))
                <a href="{{ route('teacher.quizzes.create', ['classroom_id' => $classroom->id]) }}" class="btn btn-cp">New quiz</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="cp-surface-flat p-4 h-100">
                <x-room-code :code="$classroom->room_code" />
                <p class="text-center cp-muted small mt-3 mb-0">Share this code so students can join.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="cp-surface-flat p-4 h-100 text-center">
                <div class="cp-muted text-uppercase small mb-3">Join QR</div>
                <div class="cp-qr d-inline-block">
                    @if (!empty($qrSvg))
                        {!! $qrSvg !!}
                    @elseif (!empty($classroom->qr_payload))
                        <img src="{{ $classroom->qr_payload }}" alt="Classroom QR code">
                    @elseif (Route::has('student.join'))
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode(route('student.join', ['code' => $classroom->room_code])) }}" alt="Classroom QR code">
                    @else
                        <div class="cp-muted">QR unavailable</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="cp-surface-flat p-4">
        <h2 class="h5 mb-3">Roster ({{ isset($students) ? $students->count() : ($classroom->students->count() ?? 0) }})</h2>
        @php $roster = $students ?? $classroom->students ?? collect(); @endphp
        @if ($roster->isEmpty())
            <x-empty-state title="No students yet" message="Students appear here after they join with the room code." icon="bi-person-plus" />
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roster as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td class="cp-muted">{{ $student->email }}</td>
                                <td class="cp-muted">
                                    {{ optional($student->pivot->joined_at ?? $student->joined_at ?? null)->format('M j, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
