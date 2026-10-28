<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidates user_levels into one global definition per slug, and renames
 * `mosque-officer` to `mosque-zakat`.
 *
 * Levels, permissions and menus belong to the platform owner — partners place
 * people into levels but never define them. Storing one copy per organization
 * contradicted that: 56 rows for 15 distinct slugs, so adding a permission to
 * `mosque-qurban` meant editing it once per mosque, and missing one silently
 * cost that mosque's officer their rights.
 *
 * Safe to run because every copy of a slug already held an identical permission
 * set — verified before writing this. The schema already allowed it:
 * `organization_id` is nullable and `is_global` existed but was never used.
 *
 * The rename is bundled deliberately: both touch the same rows, and one
 * migration is safer than two passes over live data. `mosque-officer` always
 * meant "Petugas Zakat" — the seeder said so, and the code already translated
 * it to messages.zakat_officer. A slug that lies misleads everyone who reads it.
 */
return new class extends Migration
{
    private const RENAMES = ['mosque-officer' => 'mosque-zakat'];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::RENAMES as $from => $to) {
                DB::table('user_levels')->where('slug', $from)->update(['slug' => $to]);
                DB::table('organization_user')->where('level_slug', $from)->update(['level_slug' => $to]);
            }

            foreach (DB::table('user_levels')->select('slug')->distinct()->pluck('slug') as $slug) {
                $copies = DB::table('user_levels')->where('slug', $slug)->orderBy('id')->get();

                // Prefer an existing global row so re-running cannot duplicate.
                $canonical = $copies->firstWhere('organization_id', null) ?? $copies->first();

                DB::table('user_levels')
                    ->where('id', $canonical->id)
                    ->update(['organization_id' => null, 'is_global' => true]);

                $obsolete = $copies->pluck('id')->reject(fn ($id) => $id === $canonical->id);

                if ($obsolete->isEmpty()) {
                    continue;
                }

                // user_menus.user_level_id is nullOnDelete; repoint rather than
                // lose the link. (Currently zero rows, but that will not stay
                // true forever.)
                DB::table('user_menus')
                    ->whereIn('user_level_id', $obsolete)
                    ->update(['user_level_id' => $canonical->id]);

                // Permissions cascade with their level. The sets were identical
                // across copies, so the canonical row already carries them.
                DB::table('user_levels')->whereIn('id', $obsolete)->delete();
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $organizations = DB::table('organizations')->select('id', 'type')->get();

            foreach (DB::table('user_levels')->whereNull('organization_id')->get() as $global) {
                $permissions = DB::table('user_level_permissions')
                    ->where('user_level_id', $global->id)
                    ->pluck('permission_name');

                $prefix = explode('-', $global->slug)[0];

                foreach ($organizations->where('type', $prefix) as $organization) {
                    $id = DB::table('user_levels')->insertGetId([
                        'organization_id' => $organization->id,
                        'name' => $global->name,
                        'slug' => $global->slug,
                        'description' => $global->description,
                        'is_global' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($permissions as $permission) {
                        DB::table('user_level_permissions')->insert([
                            'user_level_id' => $id,
                            'permission_name' => $permission,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('user_levels')->where('id', $global->id)->delete();
            }

            foreach (self::RENAMES as $from => $to) {
                DB::table('user_levels')->where('slug', $to)->update(['slug' => $from]);
                DB::table('organization_user')->where('level_slug', $to)->update(['level_slug' => $from]);
            }
        });
    }
};
