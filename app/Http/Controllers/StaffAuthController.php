<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffAuthController
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('staff.bookings.index');
        }

        return view('staff.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $credentials['email'] = mb_strtolower(trim($credentials['email']));
        $credentials['active'] = true;

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The supplied staff credentials could not be verified.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('staff.bookings.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
