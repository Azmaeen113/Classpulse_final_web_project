@extends('layouts.app')

@section('title', 'Student dashboard — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Hey, {{ Auth::user()->name }}</h1>
    <p class="cp-page-sub">Ready when your teacher goes live.</p>
@endsection

@section('content')
    <div class="mb-4"
         data-active-sessions
         data-poll-url="{{ route('api.poll.active-sessions') }}">
        <div data-active-banner>
            @php $activeSession = collect($activeSessions ?? [])->first(); @endphp
            @if ($activeSession)
                <div class="cp-live-banner d-flex flex-wrap justify-content-between align-items-center gap-3 cp-fade-in">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="cp-live-dot"></span>
                            <span class="badge badge-live">LIVE NOW</span>
                        </div>
                        <div class="fw-bold fs-4">{{ $activeSession->quiz->title ?? 'Live quiz' }}</div>
                        <div class="cp-muted">{{ $activeSession->classroom->name ?? '' }}</div>
                    </div>
                    <a href="{{ route('student.live.show', $activeSession) }}" class="btn btn-cp-live btn-lg">Jump in</a>
                </div>
            @else
                <div class="cp-surface-flat p-4 cp-muted" data-no-active>
                    <div class="cp-empty-shapes mb-2" aria-hidden="true">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    No live quiz yet. Join a classroom, then hang tight.
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-stat-card label="Classrooms" :value="isset($classrooms) ? $classrooms->count() : 0" icon="bi-building" />
        </div>
        <div class="col-md-4">
            <x-stat-card label="Active now" :value="isset($activeSessions) ? count($activeSessions) : 0" icon="bi-broadcast" />
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.history') }}" class="text-decoration-none d-block">
                <x-stat-card label="History" value="Open" icon="bi-clock-history" />
            </a>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('student.join') }}" class="btn btn-cp btn-lg"><i class="bi bi-door-open me-1"></i> Join with code</a>
        <a href="{{ route('student.history') }}" class="btn btn-cp-outline btn-lg">Your scores</a>
    </div>

    <h2 class="h5 fw-bold mb-3">Your classrooms</h2>
    @forelse ($classrooms ?? [] as $classroom)
        <div class="cp-classroom-chip">
            <div>
                <div class="fw-bold">{{ $classroom->name }}</div>
                <div class="small cp-muted">{{ $classroom->subject ?? 'No subject' }}</div>
            </div>
            <span class="code-pill">{{ $classroom->room_code }}</span>
        </div>
    @empty
        <x-empty-state title="Not in a classroom yet" message="Ask your teacher for a room code — then you are in." icon="bi-door-open">
            <a href="{{ route('student.join') }}" class="btn btn-cp">Join now</a>
        </x-empty-state>
    @endforelse
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-active-sessions]');
    if (!root || !window.ClassPulsePoller) return;
    var url = root.getAttribute('data-poll-url');
    var banner = root.querySelector('[data-active-banner]');
    new window.ClassPulsePoller({
        url: url,
        intervalMs: 3000,
        onData: function (data) {
            var sessions = data.active_sessions || data.sessions || [];
            var session = data.active_session || sessions[0] || null;
            if (!banner) return;
            if (!session) {
                banner.innerHTML =
                    '<div class="cp-surface-flat p-4 cp-muted" data-no-active>' +
                    '<div class="cp-empty-shapes mb-2" aria-hidden="true"><span></span><span></span><span></span><span></span></div>' +
                    'No live quiz yet. Join a classroom, then hang tight.</div>';
                return;
            }
            var title = (session.quiz && session.quiz.title) || session.quiz_title || 'Live quiz';
            var room = (session.classroom && session.classroom.name) || session.classroom_name || '';
            var answerUrl = session.answer_url || ('/student/live/' + session.id);
            banner.innerHTML =
                '<div class="cp-live-banner d-flex flex-wrap justify-content-between align-items-center gap-3 cp-fade-in">' +
                '<div><div class="d-flex align-items-center gap-2 mb-1"><span class="cp-live-dot"></span>' +
                '<span class="badge badge-live">LIVE NOW</span></div>' +
                '<div class="fw-bold fs-4"></div><div class="cp-muted"></div></div>' +
                '<a class="btn btn-cp-live btn-lg" href="' + answerUrl + '">Jump in</a></div>';
            banner.querySelector('.fw-bold').textContent = title;
            banner.querySelector('.cp-muted').textContent = room;
        }
    }).start();
})();
</script>
@endpush
