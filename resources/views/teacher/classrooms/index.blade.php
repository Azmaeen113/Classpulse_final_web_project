@extends('layouts.app')

@section('title', 'Classrooms — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="cp-page-title">Classrooms</h1>
            <p class="cp-page-sub mb-0">Manage room codes, QR joins, and rosters.</p>
        </div>
        <a href="{{ route('teacher.classrooms.create') }}" class="btn btn-cp"><i class="bi bi-plus-lg"></i> New classroom</a>
    </div>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ $q ?? request('q') }}" class="form-control" placeholder="Search classrooms...">
        </div>
        <div class="col-md-4">
            <button class="btn btn-cp-outline w-100" type="submit">Search</button>
        </div>
    </form>

    @if (($classrooms ?? collect())->isEmpty())
        <x-empty-state title="No classrooms" message="Create your first classroom to invite students.">
            <a href="{{ route('teacher.classrooms.create') }}" class="btn btn-cp">Create classroom</a>
        </x-empty-state>
    @else
        <div class="cp-surface-flat table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Room code</th>
                        <th>Students</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($classrooms as $classroom)
                        <tr>
                            <td class="fw-semibold">{{ $classroom->name }}</td>
                            <td class="cp-muted">{{ $classroom->subject ?? '—' }}</td>
                            <td><span class="badge badge-live">{{ $classroom->room_code }}</span></td>
                            <td>{{ $classroom->students_count ?? 0 }}</td>
                            <td class="text-end">
                                <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="btn btn-sm btn-cp-outline">View</a>
                                <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="btn btn-sm btn-cp-outline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($classrooms, 'links'))
            <div class="mt-3">{{ $classrooms->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection
