<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user();
        if (! $staff || ! $staff->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')->withErrors(['email' => 'This staff account is inactive.']);
        }

        return $next($request);
    }
}
