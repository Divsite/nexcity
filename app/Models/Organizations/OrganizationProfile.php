<?php

namespace App\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationProfile extends Model
{
    use HasFactory;

    public const UPLOAD_PATH = 'uploads/organizations';

    protected $guarded = ['id'];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $type = $this->organization?->type ?? Organization::TYPE_MOSQUE;

        return asset(self::UPLOAD_PATH . '/' . trim($type, '/') . '/' . ltrim($this->logo_path, '/'));
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        $type = $this->organization?->type ?? Organization::TYPE_MOSQUE;

        return asset(self::UPLOAD_PATH . '/' . trim($type, '/') . '/' . ltrim($this->cover_path, '/'));
    }
}
