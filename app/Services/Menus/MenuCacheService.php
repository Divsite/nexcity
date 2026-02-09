<?php

namespace App\Services\Menus;

use Illuminate\Support\Facades\Cache;

class MenuCacheService
{
    public function flush(string $context, ?int $organizationId = null): void
    {
        Cache::forget($this->key($context, null));

        if ($organizationId) {
            Cache::forget($this->key($context, $organizationId));
        }
    }

    protected function key(string $context, ?int $organizationId): string
    {
        return sprintf('menu:%s:%s', $context, $organizationId ?? 'global');
    }
}
