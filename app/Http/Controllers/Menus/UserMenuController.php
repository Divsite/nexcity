<?php

namespace App\Http\Controllers\Menus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menus\StoreUserMenuRequest;
use App\Http\Requests\Menus\UpdateUserMenuRequest;
use App\Models\Menus\UserMenu;
use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Services\Menus\MenuCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserMenuController extends Controller
{
    public function __construct(private MenuCacheService $cacheService)
    {
        $this->middleware('permission:browse-user-menus')->only('index');
        $this->middleware('permission:edit-user-menus')->only(['create', 'store', 'edit', 'update', 'destroy', 'flushCache']);
    }

    public function index(): View
    {
        return view('settings.menus.index');
    }

    public function create(): View
    {
        return view('settings.menus.create', [
            'formPayload' => $this->formPayload(new UserMenu()),
        ]);
    }

    public function store(StoreUserMenuRequest $request): JsonResponse
    {
        $menu = UserMenu::create($request->validated());
        $this->flushMenuCache($menu);

        flash()->success(__('messages.created_successfully'));

        return response()->json([
            'redirect' => route('menus.index'),
        ]);
    }

    public function edit(UserMenu $menu): View
    {
        return view('settings.menus.edit', [
            'formPayload' => $this->formPayload($menu),
        ]);
    }

    public function update(UpdateUserMenuRequest $request, UserMenu $menu): JsonResponse
    {
        $menu->update($request->validated());
        $this->flushMenuCache($menu);

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'redirect' => route('menus.edit', $menu),
        ]);
    }

    public function destroy(UserMenu $menu): RedirectResponse
    {
        $menu->delete();
        $this->flushMenuCache($menu);

        flash()->success(__('messages.deleted_successfully'));

        return redirect()->route('menus.index');
    }

    protected function availableContexts(): array
    {
        return [
            'admin' => 'Admin',
            'rt' => 'RT',
            'mosque' => 'Mosque',
            'resident' => 'Resident',
        ];
    }

    protected function flushMenuCache(UserMenu $menu): void
    {
        $this->cacheService->flush($menu->context, $menu->organization_id);
    }

    public function flushCache(): RedirectResponse
    {
        UserMenu::query()
            ->select('context', 'organization_id')
            ->distinct()
            ->get()
            ->each(function (UserMenu $menu) {
                $this->cacheService->flush($menu->context, $menu->organization_id);
            });

        flash()->success(__('messages.menu_cache_flushed'));

        return redirect()->route('menus.index');
    }

    protected function formPayload(UserMenu $menu): array
    {
        return [
            'mode' => $menu->exists ? 'edit' : 'create',
            'form' => [
                'id' => $menu->id,
                'context' => $menu->context ?? 'admin',
                'section' => $menu->section,
                'label' => $menu->label,
                'icon' => $menu->icon,
                'route_name' => $menu->route_name,
                'route_parameters' => $menu->route_parameters ?? [],
                'url' => $menu->url,
                'organization_id' => $menu->organization_id,
                'user_level_id' => $menu->user_level_id,
                'visibility_rules' => $menu->visibility_rules ?? [],
                'order' => $menu->order ?? 0,
                'is_active' => $menu->is_active ?? true,
            ],
            'options' => [
                'contexts' => $this->availableContexts(),
                'organizations' => Organization::select('id', 'name')->orderBy('name')->get(),
                'levels' => UserLevel::select('id', 'name', 'organization_id')
                    ->with('organization:id,name')
                    ->orderBy('name')
                    ->get(),
            ],
            'routes' => [
                'store' => route('menus.store'),
                'update' => $menu->exists ? route('menus.update', $menu) : null,
            ],
            'messages' => [
                'invalid_json' => __('messages.invalid_json'),
            ],
        ];
    }
}
