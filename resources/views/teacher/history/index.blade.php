@extends('layouts.app')

@section('title', 'Session history — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Session history</h1>
    <p class="cp-page-sub">Search past live sessions and open analytics.</p>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by quiz or classroom...">
        </div>
        <div class="col-md-4">
            <button class="btn btn-cp-outline w-100" type="submit">Search</button>
        </div>
    </form>

    @if (($sessions ?? collect())->isEmpty())
        <x-empty-state title="No sessions yet" message="Ended live sessions will appear here." icon="bi-clock-history" />
    @else
        <div class="cp-surface-flat table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Classroom</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr>
                            <td class="fw-semibold">{{ $session->quiz->title ?? 'Quiz' }}</td>
                            <td class="cp-muted">{{ $session->classroom->name ?? '—' }}</td>
                            <td><span class="badge text-bg-secondary text-uppercase">{{ $session->status }}</span></td>
                            <td class="cp-muted">{{ optional($session->started_at)->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="cp-muted">{{ optional($session->ended_at)->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('teacher.analytics.show', $session) }}" class="btn btn-sm btn-cp-outline">Analytics</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($sessions, 'links'))
            <div class="mt-3">{{ $sessions->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection
