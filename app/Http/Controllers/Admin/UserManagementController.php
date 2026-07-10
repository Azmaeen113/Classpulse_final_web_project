<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when(in_array($role, ['admin', 'teacher', 'student'], true), fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q', 'role'));
    }

    public function suspend(Request $request, User $user, ActivityLogService $activity): RedirectResponse
    {
        abort_if($user->isAdmin(), 422, 'Cannot suspend admin accounts.');

        $user->update(['is_active' => false]);
        $activity->log($request->user(), 'user.suspended', "Suspended {$user->email}", $request);

        return back()->with('status', 'User suspended.');
    }

    public function activate(Request $request, User $user, ActivityLogService $activity): RedirectResponse
    {
        $user->update(['is_active' => true]);
        $activity->log($request->user(), 'user.activated', "Activated {$user->email}", $request);

        return back()->with('status', 'User activated.');
    }
}
