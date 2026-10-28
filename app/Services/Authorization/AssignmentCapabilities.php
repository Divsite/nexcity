<?php

namespace App\Services\Authorization;

use App\Models\Distributions\DistributionOfficer;
use App\Models\Organizations\Organization;
use App\Models\Users\User;

/**
 * Capabilities a user holds because they were **assigned to a job**, not
 * because of the office they hold.
 *
 * A qurban or zakat committee is not a standing position: volunteers fill it
 * for one programme and it dissolves afterwards, and officers double as
 * committee members all the time. `organization_user` holds a single
 * `level_slug`, so none of that could be expressed — which is why 33 of 56
 * people at one mosque ended up sharing a level meant for zakat.
 *
 * The mechanism already existed in `distribution_officers`; this is what makes
 * it carry authority.
 *
 * **Purely additive.** Assignment grants, never withholds. Someone whose level
 * already allows this work keeps it whether or not they are assigned, so
 * turning this on cannot lock anyone out.
 *
 * See docs/architecture/mosque-structure.md
 */
class AssignmentCapabilities
{
    /**
     * A distribution stops granting anything once it is closed.
     *
     * This is what makes the grant expire on its own: nobody has to remember to
     * revoke a volunteer after Idul Adha.
     */
    public const CLOSED_STATUSES = ['completed'];

    /**
     * What being an officer on a live distribution lets you do.
     *
     * Deliberately the minimum for working a distribution in the field:
     * see it, scan people, record what happened.
     *
     * @todo `edit-mosque-charity-distributions` is broader than needed — it is
     *       simply what the mark endpoint currently requires. A narrower
     *       `mark-distribution-recipient` permission would let a volunteer
     *       record a handover without also being able to restructure the
     *       distribution.
     *
     * @var list<string>
     */
    public const GRANTED = [
        'browse-mosque-charity-distributions',
        'read-mosque-charity-distributions',
        'edit-mosque-charity-distributions',
        'scan-qurban-coupon',
        'scan-zakat-coupon',
    ];

    /**
     * Capabilities this user gains from live assignments in one organization.
     *
     * Empty for RT organizations: committees are a mosque pattern, and RT
     * distributions are worked by officers who already hold a level. Extending
     * this to RT is a product decision, not something to slip in here.
     *
     * @return list<string>
     */
    public function forOrganization(User $user, int $organizationId, ?string $organizationType = null): array
    {
        $type = $organizationType ?? Organization::query()
            ->whereKey($organizationId)
            ->value('type');

        if ($type !== Organization::TYPE_MOSQUE) {
            return [];
        }

        return $this->hasLiveAssignment($user, $organizationId) ? self::GRANTED : [];
    }

    /**
     * Is this user an officer on a distribution that is still open?
     */
    public function hasLiveAssignment(User $user, int $organizationId): bool
    {
        return DistributionOfficer::query()
            ->where('officer_id', $user->id)
            ->whereHas('distribution', fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->whereNotIn('status', self::CLOSED_STATUSES))
            ->exists();
    }

    /**
     * Is this user an officer on this specific distribution?
     *
     * Used where precision matters. The session-level grant above is coarse so
     * the UI knows to show the scan button at all; this is the fine check for
     * one distribution.
     */
    public function isOfficerOn(User $user, int $distributionId): bool
    {
        return DistributionOfficer::query()
            ->where('distribution_id', $distributionId)
            ->where('officer_id', $user->id)
            ->exists();
    }
}
