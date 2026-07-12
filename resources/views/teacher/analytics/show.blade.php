@extends('layouts.app')

@section('title', 'Session analytics — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="cp-page-title">Session analytics</h1>
            <p class="cp-page-sub mb-0">
                {{ $session->quiz->title ?? 'Quiz session' }}
                · {{ optional($session->ended_at ?? $session->started_at)->format('M j, Y g:i A') }}
            </p>
        </div>
        <a href="{{ route('teacher.analytics.export', $session) }}" class="btn btn-cp-outline">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
@endsection

@section('content')
    @php
        $rows = $rows ?? collect();
        $perQuestion = $perQuestion ?? collect();
        $participantCount = is_countable($rows) ? count($rows) : 0;
        $questionCount = is_countable($perQuestion) ? count($perQuestion) : 0;
        $avgScore = $participantCount
            ? round(collect($rows)->avg(fn ($r) => $r['points'] ?? $r['total_points'] ?? 0))
            : 0;
        $accuracyLabels = collect($perQuestion)->map(fn ($q, $i) => 'Q'.($i + 1))->values();
        $accuracyValues = collect($perQuestion)->map(fn ($q) => (float) ($q['accuracy'] ?? $q['percent'] ?? 0))->values();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-card label="Participants" :value="$participantCount" icon="bi-people" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Avg score" :value="$avgScore" icon="bi-graph-up" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Questions" :value="$questionCount" icon="bi-list-ol" />
        </div>
        <div class="col-md-3">
            <x-stat-card label="Status" :value="strtoupper($session->status ?? 'ended')" icon="bi-flag" />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="cp-surface-flat p-4">
                <h2 class="h5 mb-3">Score distribution</h2>
                <canvas id="scoreChart" height="140"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="cp-surface-flat p-4">
                <h2 class="h5 mb-3">Accuracy by question</h2>
                <canvas id="accuracyChart" height="140"></canvas>
            </div>
        </div>
    </div>

    <div class="cp-surface-flat p-4 mb-4">
        <h2 class="h5 mb-3">Leaderboard</h2>
        @forelse ($rows as $index => $row)
            <x-leaderboard-row
                :rank="$row['rank'] ?? ($index + 1)"
                :name="$row['name'] ?? $row['student_name'] ?? 'Student'"
                :points="$row['points'] ?? $row['total_points'] ?? 0"
                :avg-time="$row['avg_response_time_ms'] ?? null"
            />
        @empty
            <x-empty-state title="No results" message="This session has no scored responses." />
        @endforelse
    </div>

    <div class="cp-surface-flat p-4">
        <h2 class="h5 mb-3">Per-question breakdown</h2>
        @forelse ($perQuestion as $index => $item)
            <div class="py-2 border-bottom border-secondary border-opacity-25 d-flex justify-content-between">
                <div>
                    <div class="fw-semibold">Q{{ $index + 1 }}. {{ $item['prompt'] ?? $item['question'] ?? 'Question' }}</div>
                    <div class="small cp-muted">{{ $item['correct'] ?? 0 }} correct · {{ $item['total'] ?? 0 }} answers</div>
                </div>
                <div class="fw-bold">{{ $item['accuracy'] ?? $item['percent'] ?? 0 }}%</div>
            </div>
        @empty
            <p class="cp-muted mb-0">No question stats available.</p>
        @endforelse
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var points = @json(collect($rows)->map(fn ($r) => (int) ($r['points'] ?? $r['total_points'] ?? 0))->values());
    var buckets = [0,0,0,0,0];
    points.forEach(function (p) {
        if (p <= 20) buckets[0]++;
        else if (p <= 40) buckets[1]++;
        else if (p <= 60) buckets[2]++;
        else if (p <= 80) buckets[3]++;
        else buckets[4]++;
    });
    var accuracyLabels = @json($accuracyLabels);
    var accuracyValues = @json($accuracyValues);
    var textColor = getComputedStyle(document.documentElement).getPropertyValue('--cp-muted').trim() || '#9AA6C2';
    var grid = 'rgba(255,255,255,0.06)';

    var scoreEl = document.getElementById('scoreChart');
    if (scoreEl && window.Chart) {
        new Chart(scoreEl, {
            type: 'bar',
            data: {
                labels: ['0-20','21-40','41-60','61-80','81+'],
                datasets: [{ label: 'Students', data: buckets, backgroundColor: '#3B6FF5' }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: grid } },
                    y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: grid } }
                }
            }
        });
    }

    var accEl = document.getElementById('accuracyChart');
    if (accEl && window.Chart) {
        new Chart(accEl, {
            type: 'line',
            data: {
                labels: accuracyLabels.length ? accuracyLabels : ['Q1'],
                datasets: [{
                    label: 'Accuracy %',
                    data: accuracyValues.length ? accuracyValues : [0],
                    borderColor: '#3EE7E0',
                    backgroundColor: 'rgba(62,231,224,0.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: grid } },
                    y: { beginAtZero: true, max: 100, ticks: { color: textColor }, grid: { color: grid } }
                }
            }
        });
    }
})();
</script>
@endpush
