<?php

namespace App\Services\Authorization;

use App\Models\Distributions\DistributionOfficer;
use App\Models\Users\User;

/**
 * Capabilities a user holds because they were **assigned to a job**, not
 * because of the office they hold.
 *
 * A qurban or zakat committee is not a standing position: it is filled by
 * volunteers for one programme and dissolves afterwards. Officers double as
 * committee members all the time. `organization_user` holds a single
 * `level_slug`, so none of that could be expressed — which is why a level meant
 * for zakat ended up holding 33 people.
 *
 * The mechanism already existed: `distribution_officers` and
 * `qurban_distribution_officers` assign a user to one distribution or batch.
 * This is what makes that assignment carry authority.
 *
 * Deliberately narrow. Being assigned lets you work **that** distribution; it
 * grants nothing about any other, and it lapses when the distribution closes —
 * nobody has to remember to revoke it.
 *
 * See docs/architecture/mosque-structure.md
 */
class AssignmentCapabilities
{
    /** Distribution statuses a scan may still act on. */
    public const ACTIONABLE_STATUSES = ['draft', 'ongoing', 'in_progress', 'active', 'published'];

    /**
     * Is this user an officer on this distribution?
     */
    public function isOfficerOn(User $user, int $distributionId): bool
    {
        return DistributionOfficer::query()
            ->where('distribution_id', $distributionId)
            ->where('officer_id', $user->id)
            ->exists();
    }

    /**
     * Distributions this user is currently assigned to, within one organization.
     *
     * @return list<int>
     */
    public function assignedDistributionIds(User $user, int $organizationId): array
    {
        return DistributionOfficer::query()
            ->where('officer_id', $user->id)
            ->whereHas(
                'distribution',
                fn ($query) => $query->where('organization_id', $organizationId)
            )
            ->pluck('distribution_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
