<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\UserManagementController;
use Illuminate\View\View;

class ModulePlaceholderController extends Controller
{
    public function __invoke(string $slug = ''): View
    {
        $slug = $slug ?: (string) request()->route('slug');

        if ($slug === 'settings-user-management') {
            return app(UserManagementController::class)->index();
        }

        return view('pages.placeholder', [
            'slug' => $slug,
        ]);
    }
}
