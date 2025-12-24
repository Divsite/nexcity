<?php

namespace App\Utilities\FormProcesses;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

class ProcessUserCollector
{
    public static function get($process)
    {
        $userIds = collect();

        // Collect user IDs directly associated with the process
        if ($process->processorUsers()->exists()) {
            $userIds = $process->processorUsers->pluck('user_id');
        }

        // Collect user IDs associated through roles
        $userRoles = User::query()->whereHas('roles', function (Builder $query) use ($process) {
            $query->whereIn('id', $process->processorRoles->pluck('role_id'));
        })->pluck('id');

        return $userIds->merge($userRoles)->unique();
    }
}
