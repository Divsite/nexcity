<?php

namespace App\Providers;

use App\Models\Charities\CharityTransaction;
use App\Models\Users\User;
use App\Observers\CharityTransactionObserver;
use App\Services\Authorization\CapabilityResolver;
use App\Utilities\Themes\Theme;
use Illuminate\Support\Facades\Blade;
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
        CharityTransaction::observe(CharityTransactionObserver::class);

        $this->registerCapabilityDirective();

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

    /**
     * `@capability('add-rt-dues')` — the Blade counterpart of the `capability:`
     * middleware.
     *
     * Use it instead of `@can` for anything an organization grants. `@can`
     * reads the Spatie role, and every RT officer carries the same `rt_admin`
     * role — so a permission held only by a level reads as false and the button
     * silently disappears. That is exactly how the "Buka Bulan Iuran" form went
     * missing for the one person meant to use it.
     *
     * `@can` stays correct for account-level permissions, which do come from
     * the role.
     */
    protected function registerCapabilityDirective(): void
    {
        Blade::if('capability', function (string $permission) {
            $user = auth()->user();

            if (! $user instanceof User) {
                return false;
            }

            return app(CapabilityResolver::class)->holds($user, $permission);
        });
    }
}
