<?php

namespace App\Http\Controllers\Themes;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Utilities\Themes\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $theme = $request->input('theme', Theme::LIGHT);

        if (auth()->check()) {
            // Update in database
            $request->user()->update(['theme' => $theme]);
        }

        // Update in cookie
        // Set the cookie to expire in 10 years (10 years * 365 days * 1440 minutes/day)
        Cookie::queue('theme', $theme, 10 * 365 * 1440);

        return response()->json(['status' => true, 'message' => __('messages.successfully_update_theme')]);
    }
}
