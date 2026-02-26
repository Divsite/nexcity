<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Permissions\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InternalRoleController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        if (! $user || ! $user->can('manage-internal-roles')) {
            abort(403);
        }

        $contexts = [
            'rt' => __('messages.rt'),
            'mosque' => __('messages.mosque'),
            'umkm' => __('messages.umkm'),
            'institution' => __('messages.institution'),
        ];

        $levels = UserLevel::query()
            ->with('organization:id,type')
            ->whereHas('organization', fn ($query) => $query->whereIn('type', array_keys($contexts)))
            ->where('slug', 'not like', '%-superadmin')
            ->orderBy('name')
            ->get();

        $levelsByContext = $levels
            ->groupBy(fn ($level) => $level->organization?->type)
            ->map(function ($items) {
                return $items
                    ->unique('slug')
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($level) => [
                        'slug' => $level->slug,
                        'name' => $level->name,
                        'description' => $level->description,
                    ]);
            })
            ->toArray();

        $permissionGroupsByContext = [];
        $permissionGroupLabelsByContext = [];

        foreach (array_keys($contexts) as $context) {
            $groups = Permission::query()
                ->where('scope', 'partner')
                ->when($context, fn ($query) => $query->where(function ($sub) use ($context) {
                    $sub->whereNull('context')
                        ->orWhere('context', $context);
                }))
                ->orderBy('group')
                ->orderBy('display_name')
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->map(fn ($item) => [
                    'name' => $item->name,
                    'display_name' => __($item->display_name),
                    'description' => __($item->description),
                ])->values())
                ->toArray();

            $permissionGroupsByContext[$context] = $groups;
            $permissionGroupLabelsByContext[$context] = collect(array_keys($groups))
                ->mapWithKeys(fn ($group) => [$group => __('messages.' . $group)])
                ->toArray();
        }

        $levelPermissions = [];
        foreach ($levelsByContext as $context => $contextLevels) {
            foreach ($contextLevels as $levelInfo) {
                $level = UserLevel::query()
                    ->where('slug', $levelInfo['slug'])
                    ->whereHas('organization', fn ($query) => $query->where('type', $context))
                    ->first();

                if (! $level) {
                    continue;
                }

                $permissions = UserLevelPermission::query()
                    ->where('user_level_id', $level->id)
                    ->pluck('permission_name')
                    ->values()
                    ->toArray();

                $levelPermissions[$context . '|' . $levelInfo['slug']] = $permissions;
            }
        }

        return view('roles.internal.index', [
            'contexts' => $contexts,
            'levelsByContext' => $levelsByContext,
            'permissionGroupsByContext' => $permissionGroupsByContext,
            'permissionGroupLabelsByContext' => $permissionGroupLabelsByContext,
            'levelPermissions' => $levelPermissions,
        ]);
    }

    public function update(Request $request, string $context, string $slug): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->can('manage-internal-roles')) {
            abort(403);
        }

        $allowedContexts = ['rt', 'mosque', 'umkm', 'institution'];
        if (! in_array($context, $allowedContexts, true)) {
            abort(404);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $requested = Arr::wrap($validated['permissions'] ?? []);

        $allowed = Permission::query()
            ->where('scope', 'partner')
            ->when($context, fn ($query) => $query->where(function ($sub) use ($context) {
                $sub->whereNull('context')
                    ->orWhere('context', $context);
            }))
            ->whereIn('name', $requested)
            ->pluck('name')
            ->all();

        $levelIds = UserLevel::query()
            ->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('type', $context))
            ->pluck('id')
            ->all();

        if (empty($levelIds)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.data_not_found'),
            ], 404);
        }

        UserLevelPermission::query()
            ->whereIn('user_level_id', $levelIds)
            ->delete();

        if (! empty($allowed)) {
            $rows = collect($levelIds)
                ->flatMap(fn ($levelId) => collect($allowed)->map(fn ($name) => [
                    'user_level_id' => $levelId,
                    'permission_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]))
                ->toArray();

            UserLevelPermission::query()->insert($rows);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.updated_successfully'),
        ]);
    }

    public function storeLevel(Request $request, string $context): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->can('manage-internal-roles')) {
            abort(403);
        }

        $allowedContexts = ['rt', 'mosque', 'umkm', 'institution'];
        if (! in_array($context, $allowedContexts, true)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $slugBase = Str::slug($validated['name']);
        $slug = $context . '-' . $slugBase;
        $counter = 2;

        while (UserLevel::query()
            ->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('type', $context))
            ->exists()) {
            $slug = $context . '-' . $slugBase . '-' . $counter;
            $counter++;
        }

        $organizationIds = Organization::query()
            ->where('type', $context)
            ->pluck('id')
            ->all();

        if (empty($organizationIds)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.data_not_found'),
            ], 404);
        }

        foreach ($organizationIds as $organizationId) {
            UserLevel::updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'slug' => $slug,
                ],
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'is_global' => false,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'level' => [
                'slug' => $slug,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ],
            'message' => __('messages.created_successfully'),
        ]);
    }

    public function updateLevel(Request $request, string $context, string $slug): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->can('manage-internal-roles')) {
            abort(403);
        }

        $allowedContexts = ['rt', 'mosque', 'umkm', 'institution'];
        if (! in_array($context, $allowedContexts, true)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        UserLevel::query()
            ->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('type', $context))
            ->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.updated_successfully'),
        ]);
    }

    public function destroyLevel(string $context, string $slug): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->can('manage-internal-roles')) {
            abort(403);
        }

        $allowedContexts = ['rt', 'mosque', 'umkm', 'institution'];
        if (! in_array($context, $allowedContexts, true)) {
            abort(404);
        }

        $levelIds = UserLevel::query()
            ->where('slug', $slug)
            ->whereHas('organization', fn ($query) => $query->where('type', $context))
            ->pluck('id')
            ->all();

        if (empty($levelIds)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.data_not_found'),
            ], 404);
        }

        UserLevelPermission::query()
            ->whereIn('user_level_id', $levelIds)
            ->delete();

        UserLevel::query()
            ->whereIn('id', $levelIds)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.deleted_successfully'),
        ]);
    }
}
