<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffUserController
{
    public function index(): View
    {
        return view('staff.users.index', ['staffUsers' => StaffUser::query()->orderByDesc('active')->orderBy('display_name')->get()]);
    }

    public function create(): View
    {
        return view('staff.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:staff_users,email'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => ['required', 'string', 'min:12'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        $staffUser = StaffUser::query()->create([
            'id' => (string) Str::uuid(),
            'display_name' => trim($validated['display_name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'password' => $validated['password'],
            'active' => true,
        ]);
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'staff.created',
            'auditable_type' => StaffUser::class,
            'auditable_id' => $staffUser->id,
            'metadata' => ['email' => $staffUser->email, 'role' => $staffUser->role],
        ]);

        return redirect()->route('staff.users.index')->with('status', 'Staff account created.');
    }

    public function toggle(Request $request, StaffUser $staffUser): RedirectResponse
    {
        if ($staffUser->is($request->user())) {
            return back()->withErrors(['staff' => 'You cannot deactivate your own account.']);
        }

        $staffUser->update(['active' => ! $staffUser->active]);
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => $staffUser->active ? 'staff.activated' : 'staff.deactivated',
            'auditable_type' => StaffUser::class,
            'auditable_id' => $staffUser->id,
        ]);

        return back()->with('status', $staffUser->active ? 'Staff account activated.' : 'Staff account deactivated.');
    }

    public function updateRole(Request $request, StaffUser $staffUser): RedirectResponse
    {
        $validated = $request->validate(['role' => ['required', Rule::in(['admin', 'staff'])]]);

        if ($staffUser->is($request->user())) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }
        if ($staffUser->role === $validated['role']) {
            return back()->with('status', 'Staff role is already set to '.ucfirst($validated['role']).'.');
        }
        if ($staffUser->role === 'admin' && $validated['role'] === 'staff' && $staffUser->active
            && StaffUser::query()->where('active', true)->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'At least one active admin account must remain.']);
        }

        $before = $staffUser->role;
        $staffUser->update(['role' => $validated['role']]);
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'staff.role_updated',
            'auditable_type' => StaffUser::class,
            'auditable_id' => $staffUser->id,
            'metadata' => ['before' => ['role' => $before], 'after' => ['role' => $staffUser->role]],
        ]);

        return back()->with('status', 'Staff role updated.');
    }

    public function destroy(Request $request, StaffUser $staffUser): RedirectResponse
    {
        if ($staffUser->is($request->user())) {
            return back()->withErrors(['staff' => 'You cannot delete your own account.']);
        }
        if ($staffUser->active) {
            return back()->withErrors(['staff' => 'Deactivate the staff account before deleting it.']);
        }

        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'staff.deleted',
            'auditable_type' => StaffUser::class,
            'auditable_id' => $staffUser->id,
            'metadata' => ['email' => $staffUser->email, 'display_name' => $staffUser->display_name, 'role' => $staffUser->role],
        ]);
        $staffUser->delete();

        return back()->with('status', 'Staff account deleted.');
    }
}
