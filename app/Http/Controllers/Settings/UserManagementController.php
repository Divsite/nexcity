<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Permissions\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $membership = $user?->organizationMemberships()
            ->where('is_primary', true)
            ->first();

        if (! $membership || ! $membership->level_slug || ! Str::endsWith($membership->level_slug, '-superadmin')) {
            abort(403);
        }

        $levels = UserLevel::query()
            ->where('organization_id', $membership->organization_id)
            ->orderBy('name')
            ->get();

        $context = $this->contextFromOrganizationType($membership->organization?->type);

        $permissionGroups = Permission::query()
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

        $permissionGroupLabels = collect(array_keys($permissionGroups))
            ->mapWithKeys(fn ($group) => [$group => __('messages.' . $group)])
            ->toArray();

        $levelPermissions = UserLevelPermission::query()
            ->whereIn('user_level_id', $levels->pluck('id'))
            ->get()
            ->groupBy('user_level_id')
            ->map(fn ($items) => $items->pluck('permission_name')->values())
            ->toArray();

        return view('settings.user-management.index', [
            'levels' => $levels,
            'permissionGroups' => $permissionGroups,
            'permissionGroupLabels' => $permissionGroupLabels,
            'levelPermissions' => $levelPermissions,
        ]);
    }

    public function update(Request $request, UserLevel $level): JsonResponse
    {
        $user = auth()->user();
        $membership = $user?->organizationMemberships()
            ->where('is_primary', true)
            ->first();

        if (! $membership || ! $membership->level_slug || ! Str::endsWith($membership->level_slug, '-superadmin')) {
            abort(403);
        }

        if ($level->organization_id !== $membership->organization_id) {
            abort(403);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $requested = Arr::wrap($validated['permissions'] ?? []);
        $context = $this->contextFromOrganizationType($level->organization?->type);
        $allowed = Permission::query()
            ->where('scope', 'partner')
            ->when($context, fn ($query) => $query->where(function ($sub) use ($context) {
                $sub->whereNull('context')
                    ->orWhere('context', $context);
            }))
            ->whereIn('name', $requested)
            ->pluck('name')
            ->all();

        UserLevelPermission::query()
            ->where('user_level_id', $level->id)
            ->delete();

        if (! empty($allowed)) {
            $rows = collect($allowed)
                ->map(fn ($name) => [
                    'user_level_id' => $level->id,
                    'permission_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->toArray();

            UserLevelPermission::query()->insert($rows);
        }

        activity(__('messages.user_management'))
            ->causedBy(auth()->user())
            ->performedOn($level)
            ->log(__('messages.user_management_has_been_updated', ['name' => $level->name]));

        return response()->json([
            'success' => true,
            'message' => __('messages.updated_successfully'),
        ]);
    }

    private function contextFromOrganizationType(?string $type): ?string
    {
        return match ($type) {
            'rt' => 'rt',
            'mosque' => 'mosque',
            'umkm' => 'umkm',
            'institution' => 'institution',
            default => null,
        };
    }
}
