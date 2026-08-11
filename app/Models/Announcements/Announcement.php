<?php

namespace App\Models\Announcements;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Something an RT or a mosque wants people to know.
 *
 * The interesting part of this model is not the text — it is who is allowed to
 * read it. An RT's affairs are internal to the RT, the same rule that governs
 * iuran. A mosque's programmes are open by design. A death notice is neither:
 * it names a grieving family and gives the address of the house they are
 * sitting in, so it stays inside the neighbourhood no matter who publishes it.
 */
class Announcement extends Model
{
    use SoftDeletes;

    public const AUDIENCE_PUBLIC = 'public';

    public const AUDIENCE_MEMBERS = 'members';

    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCES = [
        self::AUDIENCE_PUBLIC,
        self::AUDIENCE_MEMBERS,
        self::AUDIENCE_STAFF,
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'event_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    // ── Relations ───────────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AnnouncementCategory::class, 'announcement_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The qurban programme, distribution or dues scheme being announced, if
     * any — so the reader can tap through instead of being told to go look.
     */
    public function announceable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Live right now: published, and not yet expired.
     *
     * A draft is not visible because someone saved it, and Sunday's kerja bakti
     * is not news on Monday.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Only what this user is allowed to read.
     *
     * Deny by default: an announcement is included because the user's
     * membership admits them, never because nothing excluded them.
     *
     * @param  list<int>  $memberOrganizationIds  organizations the user belongs to
     * @param  list<int>  $staffOrganizationIds   of those, the ones where they are pengurus
     */
    public function scopeVisibleTo(
        Builder $query,
        array $memberOrganizationIds,
        array $staffOrganizationIds,
    ): Builder {
        return $query->where(function (Builder $q) use ($memberOrganizationIds, $staffOrganizationIds) {
            // Public: only a mosque may publish this way. An RT marked public
            // would be a mistake in the data, and the read path refuses to
            // honour it rather than trusting that the write path was correct.
            $q->where(function (Builder $public) {
                $public->where('announcements.audience', self::AUDIENCE_PUBLIC)
                    ->whereHas('organization', fn (Builder $o) => $o->where('type', Organization::TYPE_MOSQUE));
            });

            if ($memberOrganizationIds !== []) {
                $q->orWhere(function (Builder $members) use ($memberOrganizationIds) {
                    $members->where('announcements.audience', self::AUDIENCE_MEMBERS)
                        ->whereIn('announcements.organization_id', $memberOrganizationIds);
                });
            }

            if ($staffOrganizationIds !== []) {
                $q->orWhere(function (Builder $staff) use ($staffOrganizationIds) {
                    $staff->where('announcements.audience', self::AUDIENCE_STAFF)
                        ->whereIn('announcements.organization_id', $staffOrganizationIds);
                });
            }
        });
    }

    /**
     * Feed order: pinned first, then the newest.
     */
    public function scopeFeedOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    // ── Rules ───────────────────────────────────────────────────────────────

    /**
     * Whether this organization may address the general public at all.
     *
     * A mosque may: its programmes are open to anyone, including people who
     * live nowhere near it. An RT may not, for the same reason its iuran is
     * private — an RT's business belongs to its own residents.
     */
    public static function mayPublishPublicly(?Organization $organization): bool
    {
        return $organization?->type === Organization::TYPE_MOSQUE;
    }

    public function isUrgent(): bool
    {
        return (bool) $this->category?->is_urgent;
    }
}
