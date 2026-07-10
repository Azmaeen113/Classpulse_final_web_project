@extends('layouts.app')

@section('title', 'Activity log — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Activity log</h1>
    <p class="cp-page-sub">Recent platform actions for audit.</p>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search actions...">
        </div>
        <div class="col-md-4">
            <button class="btn btn-cp-outline w-100" type="submit">Search</button>
        </div>
    </form>

    <div class="cp-surface-flat table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs ?? [] as $log)
                    <tr>
                        <td class="cp-muted text-nowrap">{{ optional($log->created_at)->format('M j, Y g:i A') }}</td>
                        <td>{{ $log->user->name ?? 'System' }}</td>
                        <td><span class="badge badge-cp">{{ $log->action }}</span></td>
                        <td>{{ $log->description }}</td>
                        <td class="cp-muted">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="cp-muted text-center py-4">No activity found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (isset($logs) && method_exists($logs, 'links'))
        <div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
    @endif
@endsection
