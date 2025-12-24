<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
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
