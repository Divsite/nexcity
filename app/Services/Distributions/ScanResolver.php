<?php

namespace App\Services\Distributions;

use App\Models\Distributions\DistributionRecipient;
use App\Models\Profiles\UserResidentProfile;
use App\Services\Authorization\AssignmentCapabilities;

/**
 * Answers what a scanned card means **in the context of one organization**.
 *
 * The rule the whole design rests on: **a QR identifies, it does not
 * authorize.** There is no such thing as a valid token. A card is only ever
 * entitled or not, at a given organization, for a given programme — the same
 * resident is entitled at the mosque that listed them and not at the one that
 * did not, with one identical card.
 *
 * So this never answers "is this token valid". It answers "at this
 * organization, in this programme, is this person entitled".
 *
 * See docs/modules/resident-card.md in the Flutter repo.
 */
class ScanResolver
{
    public const ENTITLED = 'entitled';
    public const ALREADY_CLAIMED = 'already_claimed';
    public const NOT_ENTITLED = 'not_entitled';
    public const UNKNOWN_CARD = 'unknown_card';

    public function __construct(protected AssignmentCapabilities $assignments)
    {
    }

    /**
     * @return array{
     *     status: string,
     *     resident: array<string, mixed>|null,
     *     entitlement: array<string, mixed>|null,
     * }
     */
    public function resolve(string $qrToken, int $organizationId, ?int $distributionId = null): array
    {
        $profile = UserResidentProfile::query()
            ->with(['user', 'neighborhoodAssociation', 'citizensAssociation'])
            ->where('qr_token', $qrToken)
            ->first();

        // An unrecognised card reveals nothing at all — not even that the token
        // is malformed rather than simply unknown.
        if (! $profile || ! $profile->user) {
            return [
                'status' => self::UNKNOWN_CARD,
                'resident' => null,
                'entitlement' => null,
            ];
        }

        $recipient = $this->findRecipient($profile->user_id, $organizationId, $distributionId);

        return [
            'status' => $this->statusFor($recipient),
            // Identity is returned in every case except an unknown card,
            // including when the person is not on the list: the officer needs
            // to know who is standing in front of them in order to explain.
            // It is capped at what the printed card already shows, so a scan
            // discloses nothing that handing the card over did not.
            'resident' => $this->residentPayload($profile),
            'entitlement' => $recipient ? $this->entitlementPayload($recipient) : null,
        ];
    }

    /**
     * The person's recipient row **at this organization only**.
     *
     * Scoping by organization is what stops one mosque learning what another
     * already gave this person. That is the real cross-organization leak, and
     * it is not rare: a resident can be listed for qurban at two mosques.
     */
    protected function findRecipient(
        int $residentId,
        int $organizationId,
        ?int $distributionId,
    ): ?DistributionRecipient {
        return DistributionRecipient::query()
            // The golongan carries the entitlement, so it is loaded with the
            // recipient rather than fetched per row afterwards.
            ->with(['distribution', 'distributionClass.source', 'officer'])
            ->where('resident_id', $residentId)
            ->when(
                $distributionId !== null,
                fn ($query) => $query->where('distribution_id', $distributionId),
            )
            ->whereHas(
                'distribution',
                fn ($query) => $query->where('organization_id', $organizationId),
            )
            // A pending row is what the officer is looking for; if there is
            // none, an already-distributed one still needs reporting so the
            // duplicate can be explained rather than silently refused.
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();
    }

    protected function statusFor(?DistributionRecipient $recipient): string
    {
        if (! $recipient) {
            return self::NOT_ENTITLED;
        }

        return $recipient->status === 'pending'
            ? self::ENTITLED
            : self::ALREADY_CLAIMED;
    }

    /**
     * @return array<string, mixed>
     */
    protected function residentPayload(UserResidentProfile $profile): array
    {
        return [
            'id' => $profile->user_id,
            'name' => $profile->user?->name,
            'rt' => $profile->neighborhoodAssociation?->name,
            'rw' => $profile->citizensAssociation?->name,
            'photo' => $profile->user?->avatar
                ? asset('uploads/avatar/' . $profile->user->avatar)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function entitlementPayload(DistributionRecipient $recipient): array
    {
        return [
            'recipient_id' => $recipient->id,
            'distribution_id' => $recipient->distribution_id,
            'distribution_title' => $recipient->distribution?->title,
            'state' => $recipient->status,
            // What this person is actually owed. The officer is standing in
            // the courtyard holding a phone and a sack of rice — "entitled" on
            // its own does not tell them what to hand over.
            //
            // The per-recipient columns are an override and are usually null:
            // 200 of 207 real rows leave them empty and take the figure from
            // their golongan instead.
            'amount_money' => $recipient->amount_money
                ?? $recipient->distributionClass?->get_money,
            'amount_rice' => $recipient->amount_rice
                ?? $recipient->distributionClass?->get_rice,
            'class_name' => $recipient->distributionClass?->source?->name,
            'distributed_at' => $recipient->distributed_at?->toIso8601String(),
            'distributed_by' => $recipient->officer?->name,
        ];
    }
}
