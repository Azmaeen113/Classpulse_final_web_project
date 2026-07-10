@extends('layouts.app')

@section('title', 'Users — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Users</h1>
    <p class="cp-page-sub">Search, filter by role, and suspend or activate accounts.</p>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email...">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All roles</option>
                <option value="teacher" @selected(request('role') === 'teacher')>Teacher</option>
                <option value="student" @selected(request('role') === 'student')>Student</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-cp-outline w-100" type="submit">Filter</button>
        </div>
    </form>

    <div class="cp-surface-flat table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users ?? [] as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td class="cp-muted">{{ $user->email }}</td>
                        <td><span class="badge badge-cp text-uppercase">{{ $user->role }}</span></td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge badge-live">Active</span>
                            @else
                                <span class="badge text-bg-danger">Suspended</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($user->is_active && Route::has('admin.users.suspend') && $user->role !== 'admin')
                                <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-cp-danger">Suspend</button>
                                </form>
                            @elseif (!$user->is_active && Route::has('admin.users.activate'))
                                <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-cp">Activate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="cp-muted text-center py-4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (isset($users) && method_exists($users, 'links'))
        <div class="mt-3">{{ $users->withQueryString()->links() }}</div>
    @endif
@endsection
