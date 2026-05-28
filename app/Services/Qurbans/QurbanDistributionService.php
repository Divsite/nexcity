<?php

namespace App\Services\Qurbans;

use App\Models\Organizations\Organization;
use App\Models\Qurbans\QurbanBeneficiary;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanCouponClaim;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QurbanDistributionService
{
    public function createBatch(array $data): QurbanDistributionBatch
    {
        $context = $this->partnerContext();

        if (! $context) {
            abort(403);
        }

        $organization = Organization::query()->findOrFail($context['organization_id']);
        $locationFields = [
            'country_id',
            'province_id',
            'city_id',
            'district_id',
            'village_id',
            'citizens_association_id',
            'neighborhood_association_id',
        ];

        $payload = Arr::only($data, array_merge($locationFields, [
            'qurban_program_id',
            'year',
            'title',
            'distribution_date',
            'claim_starts_at',
            'location_label',
            'notes',
            'coupon_color',
            'status',
        ]));

        foreach ($locationFields as $field) {
            $payload[$field] = $payload[$field] ?? $organization->{$field};
        }

        $payload['organization_id'] = $context['organization_id'];
        $payload['qurban_program_id'] = $payload['qurban_program_id'] ?? null;
        $payload['year'] = $payload['year'] ?? now()->year;
        $payload['title'] = $payload['title'] ?? 'Distribusi Daging Qurban ' . $payload['year'];
        $payload['distribution_date'] = $payload['distribution_date'] ?? ($payload['claim_starts_at'] ?? null);
        $payload['status'] = $payload['status'] ?? QurbanDistributionBatch::STATUS_ACTIVE;
        $payload['created_by'] = auth()->id();

        $batch = QurbanDistributionBatch::query()->create($payload);

        $this->syncOfficers($batch, $data['officer_ids'] ?? []);

        return $batch;
    }

    public function createBatchWithCoupons(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $batch = $this->createBatch($data);

            $sharedPayload = [
                'package_label' => $data['package_label'] ?? null,
                'meat_weight' => $data['meat_weight'] ?? null,
            ];

            $coupons = ($data['mode'] ?? 'residents') === 'blank'
                ? $this->bulkIssueBlankCoupons($batch, (int) ($data['count'] ?? 0), $sharedPayload)
                : $this->bulkIssueResidentCoupons($batch, $data['resident_ids'] ?? [], $sharedPayload);

            return [
                'batch' => $batch,
                'coupons' => $coupons,
            ];
        });
    }

    public function updateBatchWithCoupons(QurbanDistributionBatch $batch, array $data): QurbanDistributionBatch
    {
        $this->authorizeBatch($batch);

        return DB::transaction(function () use ($batch, $data) {
            $batch->update(Arr::only($data, [
                'title',
                'year',
                'distribution_date',
                'claim_starts_at',
                'notes',
                'coupon_color',
            ]));

            $this->syncOfficers($batch, $data['officer_ids'] ?? []);

            $sharedPayload = [
                'package_label' => $data['package_label'] ?? null,
                'meat_weight' => $data['meat_weight'] ?? null,
            ];

            if (($data['mode'] ?? 'residents') === 'residents') {
                $this->syncResidentCoupons($batch, $data['resident_ids'] ?? [], $sharedPayload);
            } else {
                $this->syncBlankCoupons($batch, (int) ($data['count'] ?? 0), $sharedPayload);
            }

            return $batch->refresh();
        });
    }

    public function deleteBatch(QurbanDistributionBatch $batch): void
    {
        $this->authorizeBatch($batch);

        DB::transaction(function () use ($batch) {
            $batch->load(['coupons.beneficiary']);

            if ($batch->coupons->contains(fn (QurbanCoupon $coupon) => $coupon->status === QurbanCoupon::STATUS_CLAIMED)) {
                throw ValidationException::withMessages([
                    'batch' => __('messages.claimed_coupon_cannot_be_removed'),
                ]);
            }

            $batch->officers()->detach();

            foreach ($batch->coupons as $coupon) {
                optional($coupon->beneficiary)->delete();
                $coupon->delete();
            }

            $batch->delete();
        });
    }

    public function syncOfficers(QurbanDistributionBatch $batch, array $officerIds): void
    {
        $officerIds = collect($officerIds)->filter()->unique()->values()->all();
        $batch->officers()->sync($officerIds);
    }

    public function syncResidentCoupons(QurbanDistributionBatch $batch, array $residentIds, array $data = []): void
    {
        $residentIds = collect($residentIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($residentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'resident_ids' => __('messages.recipients_required'),
            ]);
        }

        $blankCoupons = QurbanCoupon::query()
            ->where('qurban_distribution_batch_id', $batch->id)
            ->whereNull('qurban_beneficiary_id')
            ->get();

        if ($blankCoupons->contains(fn (QurbanCoupon $coupon) => $coupon->status === QurbanCoupon::STATUS_CLAIMED)) {
            throw ValidationException::withMessages([
                'resident_ids' => __('messages.claimed_coupon_cannot_be_removed'),
            ]);
        }

        foreach ($blankCoupons as $coupon) {
            $coupon->delete();
        }

        $existingCoupons = QurbanCoupon::query()
            ->with('beneficiary')
            ->where('qurban_distribution_batch_id', $batch->id)
            ->whereHas('beneficiary', function (Builder $query) {
                $query->whereNotNull('resident_id');
            })
            ->get();

        $existingByResident = $existingCoupons
            ->filter(function (QurbanCoupon $coupon) {
                return $coupon->beneficiary && $coupon->beneficiary->resident_id;
            })
            ->keyBy(function (QurbanCoupon $coupon) {
                return (int) $coupon->beneficiary->resident_id;
            });

        $removeCoupons = $existingCoupons->filter(function (QurbanCoupon $coupon) use ($residentIds) {
            $residentId = (int) ($coupon->beneficiary ? $coupon->beneficiary->resident_id : 0);

            return $residentId && ! $residentIds->contains($residentId);
        });

        $hasClaimedCoupon = false;
        foreach ($removeCoupons as $coupon) {
            if ($coupon->status === QurbanCoupon::STATUS_CLAIMED) {
                $hasClaimedCoupon = true;
                break;
            }
        }

        if ($hasClaimedCoupon) {
            throw ValidationException::withMessages([
                'resident_ids' => __('messages.claimed_coupon_cannot_be_removed'),
            ]);
        }

        foreach ($removeCoupons as $coupon) {
            if ($coupon->status !== QurbanCoupon::STATUS_ISSUED) {
                continue;
            }

            if ($coupon->beneficiary) {
                $coupon->beneficiary->delete();
            }

            $coupon->delete();
        }

        foreach ($residentIds as $residentId) {
            $residentId = (int) $residentId;

            if ($existingByResident->has($residentId)) {
                $coupon = $existingByResident->get($residentId);
                $updates = [];

                if (array_key_exists("package_label", $data)) {
                    $updates["package_label"] = $data["package_label"];
                }

                if (array_key_exists("meat_weight", $data)) {
                    $updates["meat_weight"] = $data["meat_weight"];
                }

                if ($updates) {
                    $coupon->update($updates);
                }

                continue;
            }

            $payload = $data;
            $payload["resident_id"] = $residentId;

            $this->issueCoupon($batch, $payload);
        }
    }


    public function syncBlankCoupons(QurbanDistributionBatch $batch, int $count, array $data = []): void
    {
        $this->authorizeBatch($batch);

        if ($count < 1 || $count > 1000) {
            throw ValidationException::withMessages([
                'count' => __('messages.coupon_count_invalid'),
            ]);
        }

        $coupons = QurbanCoupon::query()
            ->with('beneficiary')
            ->where('qurban_distribution_batch_id', $batch->id)
            ->orderBy('id')
            ->get();

        $hasTargetedCoupons = $coupons->contains(fn (QurbanCoupon $coupon) => (bool) $coupon->qurban_beneficiary_id);
        if ($hasTargetedCoupons) {
            if ($coupons->contains(fn (QurbanCoupon $coupon) => $coupon->status === QurbanCoupon::STATUS_CLAIMED)) {
                throw ValidationException::withMessages([
                    'mode' => __('messages.claimed_coupon_cannot_be_removed'),
                ]);
            }

            foreach ($coupons as $coupon) {
                optional($coupon->beneficiary)->delete();
                $coupon->delete();
            }

            $coupons = collect();
        }

        $currentCount = $coupons->count();

        $updates = [];
        if (array_key_exists('package_label', $data)) {
            $updates['package_label'] = $data['package_label'];
        }
        if (array_key_exists('meat_weight', $data)) {
            $updates['meat_weight'] = $data['meat_weight'];
        }
        if ($updates) {
            QurbanCoupon::query()
                ->where('qurban_distribution_batch_id', $batch->id)
                ->update($updates);
        }

        if ($count > $currentCount) {
            $this->bulkIssueBlankCoupons($batch, $count - $currentCount, $data);
            return;
        }

        if ($count === $currentCount) {
            return;
        }

        $removeCount = $currentCount - $count;
        $removableCoupons = $coupons
            ->where('status', QurbanCoupon::STATUS_ISSUED)
            ->sortByDesc('id')
            ->take($removeCount);

        if ($removableCoupons->count() < $removeCount) {
            throw ValidationException::withMessages([
                'count' => __('messages.claimed_coupon_cannot_be_removed'),
            ]);
        }

        foreach ($removableCoupons as $coupon) {
            $coupon->delete();
        }
    }

    public function issueCoupon(QurbanDistributionBatch $batch, array $data): QurbanCoupon
    {
        $this->authorizeBatch($batch);

        return DB::transaction(function () use ($batch, $data) {
            $beneficiary = isset($data['qurban_beneficiary_id'])
                ? $this->resolveBeneficiary($batch, (int) $data['qurban_beneficiary_id'])
                : $this->createBeneficiary($batch, $data['beneficiary'] ?? $data);
            $couponCode = $data['coupon_code'] ?? $this->generateCouponCode($batch);

            if ($beneficiary->resident_id && $this->residentHasCoupon($batch, (int) $beneficiary->resident_id)) {
                throw ValidationException::withMessages([
                    'resident_id' => __('messages.coupon_already_issued'),
                ]);
            }

            return QurbanCoupon::query()->create([
                'qurban_distribution_batch_id' => $batch->id,
                'qurban_beneficiary_id' => $beneficiary->id,
                'coupon_code' => $couponCode,
                'qr_code' => $data['qr_code'] ?? $couponCode,
                'package_label' => $data['package_label'] ?? null,
                'meat_weight' => $data['meat_weight'] ?? null,
                'status' => QurbanCoupon::STATUS_ISSUED,
            ]);
        });
    }

    public function issueBlankCoupon(QurbanDistributionBatch $batch, array $data = []): QurbanCoupon
    {
        $this->authorizeBatch($batch);

        $couponCode = $data['coupon_code'] ?? $this->generateCouponCode($batch);

        return QurbanCoupon::query()->create([
            'qurban_distribution_batch_id' => $batch->id,
            'qurban_beneficiary_id' => null,
            'coupon_code' => $couponCode,
            'qr_code' => $data['qr_code'] ?? $couponCode,
            'package_label' => $data['package_label'] ?? null,
            'meat_weight' => $data['meat_weight'] ?? null,
            'status' => QurbanCoupon::STATUS_ISSUED,
        ]);
    }

    public function bulkIssueCoupons(QurbanDistributionBatch $batch, array $items): Collection
    {
        $this->authorizeBatch($batch);

        return DB::transaction(function () use ($batch, $items) {
            return collect($items)
                ->map(fn (array $item) => $this->issueCoupon($batch, $item))
                ->values();
        });
    }

    public function bulkIssueBlankCoupons(QurbanDistributionBatch $batch, int $count, array $data = []): Collection
    {
        $this->authorizeBatch($batch);

        if ($count < 1 || $count > 1000) {
            throw ValidationException::withMessages([
                'count' => __('messages.coupon_count_invalid'),
            ]);
        }

        return DB::transaction(function () use ($batch, $count, $data) {
            return collect(range(1, $count))
                ->map(fn () => $this->issueBlankCoupon($batch, $data))
                ->values();
        });
    }

    public function bulkIssueResidentCoupons(QurbanDistributionBatch $batch, array $residentIds, array $data = []): Collection
    {
        $this->authorizeBatch($batch);

        $residentIds = collect($residentIds)->filter()->unique()->values();

        if ($residentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'resident_ids' => __('messages.recipients_required'),
            ]);
        }

        return DB::transaction(function () use ($batch, $residentIds, $data) {
            return $residentIds
                ->map(fn ($residentId) => $this->issueCoupon($batch, array_merge($data, [
                    'resident_id' => $residentId,
                ])))
                ->values();
        });
    }

    public function scanCoupon(string $code, ?int $scannerUserId = null, ?string $notes = null, array $scanContext = []): array
    {
        $code = trim($code);

        if ($code === '') {
            $claim = $this->createClaim(null, QurbanCouponClaim::RESULT_INVALID, $scannerUserId, $notes, $code, $scanContext);

            return [
                'success' => false,
                'result' => QurbanCouponClaim::RESULT_INVALID,
                'message' => __('messages.invalid_coupon'),
                'claim' => $claim,
            ];
        }

        return DB::transaction(function () use ($code, $scannerUserId, $notes, $scanContext) {
            $coupon = QurbanCoupon::query()
                ->with(['batch', 'beneficiary'])
                ->where(function (Builder $query) use ($code) {
                    $query->where('coupon_code', $code)
                        ->orWhere('qr_code', $code);
                })
                ->lockForUpdate()
                ->first();

            if (! $coupon) {
                $claim = $this->createClaim(null, QurbanCouponClaim::RESULT_INVALID, $scannerUserId, $notes, $code, $scanContext);

                return [
                    'success' => false,
                    'result' => QurbanCouponClaim::RESULT_INVALID,
                    'message' => __('messages.invalid_coupon'),
                    'claim' => $claim,
                ];
            }

            $context = $this->partnerContext();
            if (! $context || (int) $coupon->batch->organization_id !== (int) ($context['organization_id'] ?? 0)) {
                $claim = $this->createClaim(null, QurbanCouponClaim::RESULT_INVALID, $scannerUserId, $notes, $code, $scanContext);

                return [
                    'success' => false,
                    'result' => QurbanCouponClaim::RESULT_INVALID,
                    'message' => __('messages.invalid_coupon'),
                    'claim' => $claim,
                ];
            }

            if ($coupon->status !== QurbanCoupon::STATUS_ISSUED) {
                $result = match ($coupon->status) {
                    QurbanCoupon::STATUS_CLAIMED => QurbanCouponClaim::RESULT_ALREADY_CLAIMED,
                    QurbanCoupon::STATUS_CANCELLED => QurbanCouponClaim::RESULT_CANCELLED,
                    QurbanCoupon::STATUS_EXPIRED => QurbanCouponClaim::RESULT_EXPIRED,
                    default => QurbanCouponClaim::RESULT_INVALID,
                };

                $claim = $this->createClaim($coupon, $result, $scannerUserId, $notes, $code, $scanContext);

                return [
                    'success' => false,
                    'result' => $result,
                    'message' => match ($result) {
                        QurbanCouponClaim::RESULT_ALREADY_CLAIMED => __('messages.coupon_already_claimed'),
                        QurbanCouponClaim::RESULT_CANCELLED       => __('messages.coupon_cancelled'),
                        QurbanCouponClaim::RESULT_EXPIRED         => __('messages.coupon_expired'),
                        default                                   => __('messages.coupon_not_claimable'),
                    },
                    'coupon' => $coupon,
                    'claim' => $claim,
                ];
            }

            $coupon->update([
                'status' => QurbanCoupon::STATUS_CLAIMED,
            ]);

            $claim = $this->createClaim($coupon, QurbanCouponClaim::RESULT_SUCCESS, $scannerUserId, $notes, $code, $scanContext);

            return [
                'success' => true,
                'result' => QurbanCouponClaim::RESULT_SUCCESS,
                'message' => __('messages.coupon_claimed_successfully'),
                'coupon' => $coupon->refresh()->load(['batch', 'beneficiary']),
                'claim' => $claim,
            ];
        });
    }

    public function authorizeBatch(QurbanDistributionBatch $batch): void
    {
        $context = $this->partnerContext();

        if (! $context || (int) $batch->organization_id !== (int) ($context['organization_id'] ?? 0)) {
            abort(403);
        }
    }

    public function partnerContext(): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $membership = $user->organizationMemberships()
            ->where('level_slug', 'like', 'mosque-%')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();

        if (! $membership) {
            $profileOrgId = $user->mosqueProfile?->organization_id;
            if (! $profileOrgId) {
                return null;
            }

            return [
                'organization_id' => $profileOrgId,
                'organization_name' => $user->mosqueProfile?->organization?->name,
            ];
        }

        return [
            'organization_id' => $membership->organization_id,
            'organization_name' => $membership->organization?->name,
        ];
    }

    protected function createBeneficiary(QurbanDistributionBatch $batch, array $data): QurbanBeneficiary
    {
        $resident = isset($data['resident_id'])
            ? User::query()->with('residentProfile')->find($data['resident_id'])
            : null;

        if ($resident && ! $this->residentMatchesBatchLocation($batch, $resident)) {
            throw ValidationException::withMessages([
                'resident_id' => __('messages.resident_not_in_distribution_location'),
            ]);
        }

        if (! $resident && empty($data['name_snapshot'])) {
            throw ValidationException::withMessages([
                'name_snapshot' => __('messages.name_required'),
            ]);
        }

        $profile = $resident?->residentProfile;

        return QurbanBeneficiary::query()->create([
            'organization_id' => $batch->organization_id,
            'resident_id' => $resident?->id,
            'name_snapshot' => $data['name_snapshot'] ?? $resident?->name,
            'phone_snapshot' => $data['phone_snapshot'] ?? $resident?->phone,
            'address_snapshot' => $data['address_snapshot'] ?? $profile?->address_line,
            'category' => $data['category'] ?? ($resident ? QurbanBeneficiary::CATEGORY_RESIDENT : QurbanBeneficiary::CATEGORY_MANUAL),
            'citizens_association_id' => $data['citizens_association_id'] ?? $profile?->citizens_association_id ?? $batch->citizens_association_id,
            'neighborhood_association_id' => $data['neighborhood_association_id'] ?? $profile?->neighborhood_association_id ?? $batch->neighborhood_association_id,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    protected function resolveBeneficiary(QurbanDistributionBatch $batch, int $beneficiaryId): QurbanBeneficiary
    {
        return QurbanBeneficiary::query()
            ->where('organization_id', $batch->organization_id)
            ->findOrFail($beneficiaryId);
    }

    protected function residentHasCoupon(QurbanDistributionBatch $batch, int $residentId): bool
    {
        return QurbanCoupon::query()
            ->where('qurban_distribution_batch_id', $batch->id)
            ->whereHas('beneficiary', fn (Builder $query) => $query->where('resident_id', $residentId))
            ->exists();
    }

    protected function residentMatchesBatchLocation(QurbanDistributionBatch $batch, User $resident): bool
    {
        $profile = $resident->residentProfile;
        if (! $profile) {
            return false;
        }

        if ($batch->country_id && (int) $profile->country_id !== (int) $batch->country_id) {
            return false;
        }

        if ($batch->province_id && (int) $profile->province_id !== (int) $batch->province_id) {
            return false;
        }

        if ($batch->city_id && (int) $profile->city_id !== (int) $batch->city_id) {
            return false;
        }

        if ($batch->district_id && (int) $profile->district_id !== (int) $batch->district_id) {
            return false;
        }

        if ($batch->village_id && (int) $profile->village_id !== (int) $batch->village_id) {
            return false;
        }

        if ($batch->citizens_association_id && (int) $profile->citizens_association_id !== (int) $batch->citizens_association_id) {
            return false;
        }

        if ($batch->neighborhood_association_id && (int) $profile->neighborhood_association_id !== (int) $batch->neighborhood_association_id) {
            return false;
        }

        return true;
    }

    protected function createClaim(?QurbanCoupon $coupon, string $result, ?int $scannerUserId, ?string $notes, ?string $scannedCode = null, array $scanContext = []): QurbanCouponClaim
    {
        $beneficiary = $coupon?->relationLoaded('beneficiary')
            ? $coupon->beneficiary
            : $coupon?->beneficiary()->first();

        $batchId = $coupon?->qurban_distribution_batch_id ?: ($scanContext['qurban_distribution_batch_id'] ?? null);
        if (! $coupon && $batchId) {
            $batch = QurbanDistributionBatch::query()->find($batchId);
            if ($batch) {
                $this->authorizeBatch($batch);
            } else {
                $batchId = null;
            }
        }

        return QurbanCouponClaim::query()->create([
            'qurban_distribution_batch_id' => $batchId,
            'qurban_coupon_id' => $coupon?->id,
            'scanned_code' => $scannedCode,
            'claimed_by_user_id' => $result === QurbanCouponClaim::RESULT_SUCCESS
                ? $beneficiary?->resident_id
                : null,
            'claimed_at' => $result === QurbanCouponClaim::RESULT_SUCCESS ? now() : null,
            'scan_result' => $result,
            'scanner_user_id' => $scannerUserId ?? auth()->id(),
            'scan_latitude' => $scanContext['scan_latitude'] ?? null,
            'scan_longitude' => $scanContext['scan_longitude'] ?? null,
            'scan_location_label' => $scanContext['scan_location_label'] ?? null,
            'ip_address' => $scanContext['ip_address'] ?? null,
            'user_agent' => $scanContext['user_agent'] ?? null,
            'notes' => $notes,
            'meta' => $scanContext['meta'] ?? null,
        ]);
    }

    protected function generateCouponCode(QurbanDistributionBatch $batch): string
    {
        $year = (int) ($batch->year ?: now()->year);
        $organizationCode = str_pad(strtoupper(base_convert((string) $batch->organization_id, 10, 36)), 2, '0', STR_PAD_LEFT);
        $rtNumber = $batch->neighborhoodAssociation?->number
            ?? $batch->neighborhoodAssociation()->value('number')
            ?? $batch->neighborhood_association_id
            ?? 0;
        $rtCode = str_pad((string) $rtNumber, 3, '0', STR_PAD_LEFT);
        $sequence = QurbanCoupon::query()
                ->whereHas('batch', function (Builder $query) use ($batch, $year) {
                    $query->where('organization_id', $batch->organization_id)
                        ->where('year', $year)
                        ->where('neighborhood_association_id', $batch->neighborhood_association_id);
                })
                ->count() + 1;

        do {
            $code = 'Q' . substr((string) $year, -2) . $organizationCode . 'R' . $rtCode . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (QurbanCoupon::query()->where('coupon_code', $code)->exists());

        return $code;
    }
}
