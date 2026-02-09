<?php

namespace App\Models\Menus;

use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class UserMenu extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'route_parameters' => 'array',
        'visibility_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->route_name && Route::has($this->route_name)) {
            try {
                return route($this->route_name, $this->route_parameters ?? []);
            } catch (\Throwable $e) {
                return '#';
            }
        }

        return $this->url ?? '#';
    }

    public function isActive(): bool
    {
        if ($this->route_name && Route::currentRouteName()) {
            return Str::startsWith(Route::currentRouteName(), $this->route_name);
        }

        if ($this->url) {
            $path = trim(parse_url($this->url, PHP_URL_PATH) ?? $this->url, '/');
            return request()->is($path) || request()->fullUrlIs($this->url);
        }

        return false;
    }
}
