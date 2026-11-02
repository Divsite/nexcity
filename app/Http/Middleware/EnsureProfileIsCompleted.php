<?php

namespace App\Http\Middleware;

use App\Support\DefaultPassword;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        // Signed in with a password nobody chose: nothing else opens until it
        // is changed. Independent of the global switch below, which applies
        // to every account and is off.
        if (auth()->check() && $request->session()->get(DefaultPassword::SESSION_KEY)) {
            flash()->info(__('messages.default_password_change_first'));
            return redirect()->route('profile.change-password-form');
        }

        if (config('core.ensure_password_is_changed') && auth()->check() && !auth()->user()->password_changed_at) {
            flash()->info(__('messages.please_change_your_password_to_continue'));
            return redirect()->route(config('core.password_route_name'));
        }

        if (config('core.ensure_profile_is_completed') && auth()->check() && !auth()->user()->profile_completed) {
            flash()->info(__('messages.please_complete_your_profile_to_continue'));
            return redirect()->route(config('core.profile_route_name'));
        }

        return $next($request);
    }
}
