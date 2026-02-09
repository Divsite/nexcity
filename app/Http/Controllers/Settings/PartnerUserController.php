<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\CreatePartnerUserRequest;
use App\Http\Requests\Users\UpdatePartnerUserRequest;
use App\Models\Organizations\UserLevel;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class PartnerUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read-users')->only('index', 'create', 'store', 'edit', 'update', 'destroy');
    }

    public function index()
    {
        return view('settings/partner-users/index');
    }

    public function create()
    {
        $membership = $this->resolvePrimaryMembership();

        $levels = UserLevel::query()
            ->where('organization_id', $membership['organization_id'])
            ->orderBy('name')
            ->get();

        return view('settings/partner-users/create', [
            'levels' => $levels,
        ]);
    }

    public function store(CreatePartnerUserRequest $request)
    {
        $membership = $this->resolvePrimaryMembership();
        $validated = $request->validated();

        $roleName = $this->roleNameForContext($membership['context']);

        $model = new User();
        $model->name = $validated['name'];
        $model->username = $this->generatePartnerUsername(
            $validated['name'],
            $membership['organization_slug'],
            $validated['level_slug']
        );
        $model->email = $validated['email'];
        $model->password = Hash::make($validated['password']);

        if (! empty($validated['phone'])) {
            $model->phone = phone($validated['phone'], 'MY');
        }

        $fileName = Str::random(30) . '.png';
        if ($request->hasFile('avatar')) {
            $request->file('avatar')->storePubliclyAs(User::AVATAR_PATH, $fileName, 'public');
            $model->initial_name = User::AVATAR_NOT_INITIAL_NAME;
        } else {
            Avatar::create($model->name)->save(storage_path('app/public/' . User::AVATAR_PATH . $fileName), 100);
            $model->initial_name = User::AVATAR_INITIAL_NAME;
        }

        $model->avatar = $fileName;
        $model->email_verified_at = null;
        $model->save();

        $model->syncRoles([$roleName]);

        $model->organizations()->syncWithoutDetaching([
            $membership['organization_id'] => [
                'role' => $roleName,
                'level_slug' => $validated['level_slug'],
                'is_primary' => false,
                'joined_at' => now(),
            ],
        ]);

        $this->syncProfileForContext($model, $membership['context'], $membership['organization_id']);

        activity(__('messages.users'))
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->log(__('messages.users_has_been_created', ['name' => $model->name]));

        flash()->success(__('messages.user_successfully_created'));

        return response()->json(['success' => true, 'redirect' => route('settings.users.index')]);
    }

    public function edit(User $user)
    {
        $membership = $this->resolvePrimaryMembership();

        $this->ensureUserInOrganization($user, $membership['organization_id']);

        $levels = UserLevel::query()
            ->where('organization_id', $membership['organization_id'])
            ->orderBy('name')
            ->get();

        $pivot = $user->organizationMemberships()
            ->where('organization_id', $membership['organization_id'])
            ->first();

        return view('settings/partner-users/edit', [
            'model' => $user,
            'levels' => $levels,
            'currentLevelSlug' => $pivot?->level_slug,
        ]);
    }

    public function update(UpdatePartnerUserRequest $request, User $user)
    {
        $membership = $this->resolvePrimaryMembership();

        $this->ensureUserInOrganization($user, $membership['organization_id']);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->initial_name = $validated['initial_name'];

        if (! empty($validated['phone'])) {
            $user->phone = phone($validated['phone'], 'MY');
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            Storage::disk('public')->delete(User::AVATAR_PATH . $user->avatar);
            $avatar = $request->file('avatar');
            $fileName = $avatar->hashName();
            $avatar->storePublicly(User::AVATAR_PATH, 'public');
            $user->avatar = $fileName;
            $user->initial_name = User::AVATAR_NOT_INITIAL_NAME;
        }

        if ($user->initial_name == User::AVATAR_INITIAL_NAME) {
            Storage::disk('public')->delete(User::AVATAR_PATH . $user->avatar);
            $fileName = Str::random(30) . '.png';
            Avatar::create($user->name)->save(storage_path('app/public/' . User::AVATAR_PATH . $fileName), 100);
            $user->avatar = $fileName;
        }

        $user->update();

        $user->organizations()->syncWithoutDetaching([
            $membership['organization_id'] => [
                'role' => $this->roleNameForContext($membership['context']),
                'level_slug' => $validated['level_slug'],
            ],
        ]);

        $this->syncProfileForContext($user, $membership['context'], $membership['organization_id']);

        activity(__('messages.users'))
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log(__('messages.users_has_been_updated', ['name' => $user->name]));

        flash()->success(__('messages.user_successfully_updated'));

        return response()->json(['success' => true, 'redirect' => route('settings.users.index')]);
    }

    public function destroy(User $user)
    {
        $membership = $this->resolvePrimaryMembership();

        $this->ensureUserInOrganization($user, $membership['organization_id']);

        $user->delete();

        flash()->success(__('messages.user_successfully_deleted'));

        activity(__('messages.users'))
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log(__('messages.users_has_been_deleted', ['name' => $user->name]));

        return response()->json(['success' => true, 'redirect' => route('settings.users.index')]);
    }

    private function resolvePrimaryMembership(): array
    {
        $user = auth()->user();
        $membership = $user?->organizationMemberships()
            ->where('is_primary', true)
            ->with('organization:id,slug,type')
            ->first();

        if (! $membership || ! $membership->level_slug || ! Str::endsWith($membership->level_slug, '-superadmin')) {
            abort(403);
        }

        $context = $membership->organization?->type;

        if (! in_array($context, ['rt', 'mosque', 'umkm', 'institution'], true)) {
            abort(403);
        }

        return [
            'organization_id' => $membership->organization_id,
            'context' => $context,
            'organization_slug' => $membership->organization?->slug ?? 'partner',
        ];
    }

    private function ensureUserInOrganization(User $user, int $organizationId): void
    {
        $exists = $user->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $exists) {
            abort(404);
        }
    }

    private function roleNameForContext(string $context): string
    {
        return match ($context) {
            'rt' => 'rt_admin',
            'mosque' => 'mosque_admin',
            'umkm' => 'umkm_admin',
            'institution' => 'institusi_admin',
            default => 'user',
        };
    }

    private function syncProfileForContext(User $user, string $context, int $organizationId): void
    {
        switch ($context) {
            case 'mosque':
                $user->mosqueProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['organization_id' => $organizationId]
                );
                break;
            case 'rt':
                $user->rtProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['organization_id' => $organizationId]
                );
                break;
            case 'umkm':
                $user->umkmProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['organization_id' => $organizationId]
                );
                break;
            case 'institution':
                $user->institutionProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['organization_id' => $organizationId]
                );
                break;
            default:
                break;
        }
    }

    private function generatePartnerUsername(string $name, string $organizationSlug, string $levelSlug): string
    {
        $nameSlug = Str::slug($name, '.');
        $orgSlug = Str::slug($organizationSlug, '.');
        $level = Str::slug($levelSlug, '.');
        $base = trim($nameSlug . '.' . $orgSlug . '.' . $level, '.');
        $suffix = Str::lower(Str::random(4));
        $username = Str::limit($base . '.' . $suffix, 255, '');

        while (User::where('username', $username)->exists()) {
            $suffix = Str::lower(Str::random(4));
            $username = Str::limit($base . '.' . $suffix, 255, '');
        }

        return $username;
    }
}
