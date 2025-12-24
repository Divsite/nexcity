<?php

namespace App\Providers;

use App\Utilities\Themes\Theme;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Flash\Flash;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $colorMode = auth()->user()->theme ?? Theme::LIGHT;
            } else {
                $colorMode = request()->cookie('theme', Theme::LIGHT);
            }

            // Default view for backend
            $theme = collect([
                'layout' => config('core.dashboard_layouts.layout'),
                'width' => config('core.dashboard_layouts.width'),
                'topbar' => config('core.dashboard_layouts.topbar'),
                'layout-style' => config('core.dashboard_layouts.layout_style'),
                'color-mode' => $colorMode,
            ]);

            /*
            // View for user
            if (Auth::check() && Auth::user()->getRoleNames()->contains('user')) {
                $theme['layout'] = 'horizontal';
                $theme['width'] = 'boxed';
                $theme['topbar'] = 'dark';
            }
            */

            $view->with('theme', $theme);
        });

        Flash::levels([
            'success' => 'text-bg-success',
            'info' => 'text-bg-info',
            'warning' => 'text-bg-warning',
            'error' => 'text-bg-danger',
        ]);
    }
}
