<?php

namespace App\Console\Commands;

use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Reports where a `*-superadmin` level grants less than its role does.
 *
 * The invariant: an organization's superadmin level must hold every
 * organization-scoped permission its role holds. Anything less means that once
 * authorization moves from role-based to level-based, that person silently
 * loses access they are entitled to.
 *
 * This is a *reporting* tool first. `--fix` only ever inserts missing rows; it
 * never removes a grant, so it cannot lock anyone out.
 *
 * See docs/operations/authorization-audit.md
 */
class AuditLevelPermissions extends Command
{
    protected $signature = 'levels:audit
                            {--fix : Insert the missing permissions}';

    protected $description = 'Check that superadmin levels grant everything their role does';

    /** Which Spatie role backs each organization type. */
    protected const ROLE_FOR_TYPE = [
        Organization::TYPE_MOSQUE => 'mosque_admin',
        Organization::TYPE_RT => 'rt_admin',
    ];

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $totalMissing = 0;
        $rows = [];

        foreach (self::ROLE_FOR_TYPE as $type => $roleName) {
            $expected = $this->organizationScopedPermissions($roleName);

            if ($expected->isEmpty()) {
                $this->warn("Role {$roleName} has no organization-scoped permissions — skipped.");
                continue;
            }

            $levels = UserLevel::query()
                ->where('slug', "{$type}-superadmin")
                ->with('organization')
                ->get();

            foreach ($levels as $level) {
                $held = UserLevelPermission::query()
                    ->where('user_level_id', $level->id)
                    ->pluck('permission_name');

                $missing = $expected->diff($held)->values();
                $totalMissing += $missing->count();

                $rows[] = [
                    $level->organization?->name ?? "org #{$level->organization_id}",
                    $level->slug,
                    $held->count(),
                    $missing->count(),
                ];

                if ($fix && $missing->isNotEmpty()) {
                    UserLevelPermission::query()->insert(
                        $missing->map(fn (string $name) => [
                            'user_level_id' => $level->id,
                            'permission_name' => $name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->all()
                    );
                }
            }
        }

        $this->table(['Organisasi', 'Level', 'Punya', 'Kurang'], $rows);

        if ($totalMissing === 0) {
            $this->info('Setiap level superadmin sudah lengkap.');

            return self::SUCCESS;
        }

        if ($fix) {
            $this->info("Menambahkan {$totalMissing} permission yang kurang.");

            return self::SUCCESS;
        }

        $this->warn("{$totalMissing} permission kurang. Jalankan dengan --fix untuk menambahkannya.");

        // Non-zero so CI or a deploy check can fail on drift.
        return self::FAILURE;
    }

    /**
     * Permissions of [$roleName] that only make sense inside an organization.
     *
     * Account-level ones (my-account, change-password) are excluded: they are
     * granted by the role and never by a level.
     *
     * @return Collection<int, string>
     */
    protected function organizationScopedPermissions(string $roleName): Collection
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return collect();
        }

        return $role->permissions
            ->pluck('name')
            ->filter(fn (string $name) => (bool) preg_match(
                '/^(browse|read|add|edit|delete|print|scan)-(mosque|rt|qurban)/',
                $name,
            ) || str_starts_with($name, 'scan-'))
            ->unique()
            ->values();
    }
}
