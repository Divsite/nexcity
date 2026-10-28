<?php

namespace App\Models\Announcements;

use App\Models\Organizations\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The kinds of announcement that exist. Seeded, but editable — a takmir who
 * needs a category we never thought of should not need a deployment.
 */
class AnnouncementCategory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_urgent' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * The categories one kind of organization can actually use.
     *
     * An RT has no jadwal kajian and a mosque has no siskamling. Offering both
     * lists to both would make the form a menu of things that do not apply.
     */
    public function scopeForOrganizationType(Builder $query, string $type): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($type) {
                $q->where('applies_to', 'both')->orWhere('applies_to', $type);
            })
            ->orderBy('sort_order');
    }

    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->forOrganizationType((string) $organization->type);
    }
}
