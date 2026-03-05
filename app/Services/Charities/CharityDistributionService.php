<?php

namespace App\Services\Charities;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionFundSetting;
use App\Models\Distributions\DistributionFundSource;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Distributions\DistributionRecipientStatusLog;
use App\Models\Charities\CharityTransaction;
use App\Services\Charities\CharityReceiptTotalsService;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionTypes\DistributionType;
use App\Models\CharityTypes\CharityType;
use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Country;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CharityDistributionService
{
    public function store(array $data): Distribution
    {
        $context = $this->partnerContext();

        if (! $context) {
            abort(403);
        }

        $organization = Organization::query()->findOrFail($context['organization_id']);
        $distributionType = DistributionType::query()->where('slug', 'zakat')->firstOrFail();

        $distributionClass = DistributionClass::query()
            ->with('source')
            ->where('id', $data['distribution_class_id'])
            ->where('year', (int) ($data['year'] ?? now()->year))
            ->when($context['organization_id'], fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->firstOrFail();

        $isInternal = (bool) $distributionClass->is_internal;
        $useManual = $isInternal ? true : ! empty($data['use_manual_recipients']);
        $neighborhoodId = $useManual
            ? null
            : ($data['neighborhood_association_id'] ?? $organization->neighborhood_association_id);

        $existingQuery = Distribution::query()
            ->where('organization_id', $context['organization_id'])
            ->where('year', (int) ($data['year'] ?? now()->year))
            ->whereHas('recipients', fn (Builder $builder) => $builder->where('distribution_class_id', $distributionClass->id));

        if (! $useManual) {
            $existingQuery->where('neighborhood_association_id', $neighborhoodId);
        }

        $existing = $existingQuery->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'distribution_class_id' => __('messages.distribution_class_year_exists'),
            ]);
        }

        $payload = [
            'organization_id' => $context['organization_id'],
            'distribution_type_id' => $distributionType->id,
            'year' => (int) ($data['year'] ?? now()->year),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ];

        $locationFields = [
            'country_id',
            'province_id',
            'city_id',
            'district_id',
            'village_id',
            'citizens_association_id',
            'neighborhood_association_id',
        ];

        foreach ($locationFields as $field) {
            $payload[$field] = $data[$field] ?? $organization->{$field};
        }

        if ($isInternal) {
            foreach ($locationFields as $field) {
                $payload[$field] = null;
            }
        }

        if ($useManual) {
            $payload['neighborhood_association_id'] = null;
        } else {
            $payload['neighborhood_association_id'] = $neighborhoodId;
        }

        $neighborhood = $payload['neighborhood_association_id']
            ? NeighborhoodAssociation::query()->find($payload['neighborhood_association_id'])
            : null;
        $citizens = $payload['citizens_association_id']
            ? CitizensAssociation::query()->find($payload['citizens_association_id'])
            : null;

        $locationTitle = null;
        if ($neighborhood || $citizens) {
            $locationTitle = trim(sprintf('RT %s / RW %s', $neighborhood?->number ?? '-', $citizens?->number ?? '-'));
        }

        $titleParts = [
            'Zakat',
            $distributionClass->source?->name,
            $locationTitle,
            $payload['year'],
        ];

        $payload['title'] = trim(implode(' ', array_filter($titleParts)));

        $slugParts = array_filter([
            'zakat',
            $distributionClass->source?->slug,
            $neighborhood?->number ? ('rt-' . $neighborhood->number) : null,
            $citizens?->number ? ('rw-' . $citizens->number) : null,
            $payload['year'],
        ]);

        $payload['meta'] = [
            'code' => Str::slug(implode('-', $slugParts)),
        ];

        $officerIds = collect($data['officer_ids'] ?? [])->filter()->unique()->values();
        $recipientIds = $isInternal ? collect() : collect($data['recipient_ids'] ?? [])->filter()->unique()->values();
        $manualRecipients = $isInternal ? collect() : collect($data['manual_recipients'] ?? [])
            ->map(fn ($item) => Arr::only($item, ['name', 'phone', 'address']))
            ->filter(fn ($item) => ! empty($item['name']))
            ->values();

        if ($officerIds->isEmpty()) {
            throw ValidationException::withMessages([
                'officer_ids' => __('messages.recipients_required'),
            ]);
        }

        if ($isInternal && $officerIds->isNotEmpty()) {
            $duplicateOfficers = DistributionRecipient::query()
                ->whereIn('officer_id', $officerIds)
                ->where('distribution_class_id', '!=', $distributionClass->id)
                ->whereHas('distribution', function (Builder $builder) use ($context, $data) {
                    $builder->where('organization_id', $context['organization_id'])
                        ->where('year', (int) ($data['year'] ?? now()->year));
                })
                ->exists();

            if ($duplicateOfficers) {
                throw ValidationException::withMessages([
                    'officer_ids' => __('messages.officer_already_assigned'),
                ]);
            }
        }

        if (! $isInternal && $officerIds->isNotEmpty()) {
            $neighborhoodId = $payload['neighborhood_association_id'] ?? null;
            $duplicateOfficers = Distribution::query()
                ->where('organization_id', $context['organization_id'])
                ->where('year', (int) ($payload['year'] ?? now()->year))
                ->when($neighborhoodId, fn (Builder $builder) => $builder->where('neighborhood_association_id', '!=', $neighborhoodId))
                ->whereHas('officers', fn (Builder $builder) => $builder->whereIn('officer_id', $officerIds))
                ->exists();

            if ($duplicateOfficers) {
                throw ValidationException::withMessages([
                    'officer_ids' => __('messages.officer_already_assigned'),
                ]);
            }
        }

        if ($recipientIds->isNotEmpty() && ! $useManual) {
            $neighborhoodId = $payload['neighborhood_association_id'] ?? null;
            $duplicateRecipients = DistributionRecipient::query()
                ->whereIn('resident_id', $recipientIds)
                ->where('distribution_class_id', '!=', $distributionClass->id)
                ->whereHas('distribution', function (Builder $builder) use ($context, $payload, $neighborhoodId) {
                    $builder->where('organization_id', $context['organization_id'])
                        ->where('year', (int) ($payload['year'] ?? now()->year));

                    if ($neighborhoodId) {
                        $builder->where('neighborhood_association_id', $neighborhoodId);
                    }
                })
                ->exists();

            if ($duplicateRecipients) {
                throw ValidationException::withMessages([
                    'recipient_ids' => __('messages.recipient_already_assigned'),
                ]);
            }
        }

        return DB::transaction(function () use ($payload, $officerIds, $recipientIds, $manualRecipients, $distributionClass, $isInternal) {
            $distribution = Distribution::create($payload);

            if ($officerIds->isNotEmpty() && ! $isInternal) {
                $rows = $officerIds->map(fn ($id) => [
                    'distribution_id' => $distribution->id,
                    'officer_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_officers')->insert($rows->all());
            }

            if ($recipientIds->isNotEmpty()) {
                $rows = $recipientIds->map(fn ($id) => [
                    'distribution_id' => $distribution->id,
                    'resident_id' => $id,
                    'distribution_class_id' => $distributionClass->id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_recipients')->insert($rows->all());
            }

            if ($manualRecipients->isNotEmpty()) {
                $rows = $manualRecipients->map(fn ($item) => [
                    'distribution_id' => $distribution->id,
                    'resident_id' => null,
                    'recipient_name' => $item['name'] ?? null,
                    'recipient_phone' => $item['phone'] ?? null,
                    'recipient_address' => $item['address'] ?? null,
                    'distribution_class_id' => $distributionClass->id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_recipients')->insert($rows->all());
            }

            if ($isInternal && $officerIds->isNotEmpty()) {
                $officers = User::query()
                    ->whereIn('id', $officerIds)
                    ->get(['id', 'name'])
                    ->keyBy('id');

                $rows = $officerIds->map(function ($id) use ($distribution, $distributionClass, $officers) {
                    return [
                        'distribution_id' => $distribution->id,
                        'resident_id' => null,
                        'officer_id' => $id,
                        'recipient_name' => $officers->get($id)?->name,
                        'group_label' => null,
                        'amount_money' => $distributionClass->get_money,
                        'amount_rice' => $distributionClass->get_rice,
                        'distribution_class_id' => $distributionClass->id,
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
                DB::table('distribution_recipients')->insert($rows->all());
            }

            activity(__('messages.distributions'))
                ->causedBy(auth()->user())
                ->performedOn($distribution)
                ->log(__('messages.distributions_has_been_created', ['name' => $distribution->title ?? '#' . $distribution->id]));

            return $distribution;
        });
    }

    public function update(Distribution $distribution, array $data): Distribution
    {
        $context = $this->partnerContext();

        if (! $context || $distribution->organization_id !== ($context['organization_id'] ?? null)) {
            abort(403);
        }

        $hasDistributed = $distribution->recipients()
            ->where('status', 'distributed')
            ->exists();

        if ($hasDistributed) {
            throw ValidationException::withMessages([
                'distribution_class_id' => __('messages.distribution_cannot_edit_distributed'),
            ]);
        }

        $organization = Organization::query()->findOrFail($context['organization_id']);
        $distributionType = DistributionType::query()->where('slug', 'zakat')->firstOrFail();

        $distributionClass = DistributionClass::query()
            ->with('source')
            ->where('id', $data['distribution_class_id'])
            ->where('year', (int) ($data['year'] ?? now()->year))
            ->when($context['organization_id'], fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->firstOrFail();

        $isInternal = (bool) $distributionClass->is_internal;
        $useManual = $isInternal ? true : ! empty($data['use_manual_recipients']);
        $neighborhoodId = $useManual
            ? null
            : ($data['neighborhood_association_id'] ?? $organization->neighborhood_association_id);

        $existingQuery = Distribution::query()
            ->where('organization_id', $context['organization_id'])
            ->where('year', (int) ($data['year'] ?? now()->year))
            ->where('id', '!=', $distribution->id)
            ->whereHas('recipients', fn (Builder $builder) => $builder->where('distribution_class_id', $distributionClass->id));

        if (! $useManual) {
            $existingQuery->where('neighborhood_association_id', $neighborhoodId);
        }

        $existing = $existingQuery->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'distribution_class_id' => __('messages.distribution_class_year_exists'),
            ]);
        }

        $payload = [
            'organization_id' => $context['organization_id'],
            'distribution_type_id' => $distributionType->id,
            'year' => (int) ($data['year'] ?? now()->year),
            'status' => $distribution->status ?? 'pending',
            'created_by' => $distribution->created_by ?? auth()->id(),
        ];

        $locationFields = [
            'country_id',
            'province_id',
            'city_id',
            'district_id',
            'village_id',
            'citizens_association_id',
            'neighborhood_association_id',
        ];

        foreach ($locationFields as $field) {
            $payload[$field] = $data[$field] ?? $organization->{$field};
        }

        if ($isInternal) {
            foreach ($locationFields as $field) {
                $payload[$field] = null;
            }
        }

        if ($useManual) {
            $payload['neighborhood_association_id'] = null;
        } else {
            $payload['neighborhood_association_id'] = $neighborhoodId;
        }

        $neighborhood = $payload['neighborhood_association_id']
            ? NeighborhoodAssociation::query()->find($payload['neighborhood_association_id'])
            : null;
        $citizens = $payload['citizens_association_id']
            ? CitizensAssociation::query()->find($payload['citizens_association_id'])
            : null;

        $locationTitle = null;
        if ($neighborhood || $citizens) {
            $locationTitle = trim(sprintf('RT %s / RW %s', $neighborhood?->number ?? '-', $citizens?->number ?? '-'));
        }

        $titleParts = [
            'Zakat',
            $distributionClass->source?->name,
            $locationTitle,
            $payload['year'],
        ];

        $payload['title'] = trim(implode(' ', array_filter($titleParts)));

        $slugParts = array_filter([
            'zakat',
            $distributionClass->source?->slug,
            $neighborhood?->number ? ('rt-' . $neighborhood->number) : null,
            $citizens?->number ? ('rw-' . $citizens->number) : null,
            $payload['year'],
        ]);

        $payload['meta'] = [
            'code' => Str::slug(implode('-', $slugParts)),
        ];

        $officerIds = collect($data['officer_ids'] ?? [])->filter()->unique()->values();
        $recipientIds = $isInternal ? collect() : collect($data['recipient_ids'] ?? [])->filter()->unique()->values();
        $manualRecipients = $isInternal ? collect() : collect($data['manual_recipients'] ?? [])
            ->map(fn ($item) => Arr::only($item, ['name', 'phone', 'address']))
            ->filter(fn ($item) => ! empty($item['name']))
            ->values();

        if ($officerIds->isEmpty()) {
            throw ValidationException::withMessages([
                'officer_ids' => __('messages.recipients_required'),
            ]);
        }

        if ($isInternal && $officerIds->isNotEmpty()) {
            $duplicateOfficers = DistributionRecipient::query()
                ->whereIn('officer_id', $officerIds)
                ->where('distribution_id', '!=', $distribution->id)
                ->where('distribution_class_id', '!=', $distributionClass->id)
                ->whereHas('distribution', function (Builder $builder) use ($context, $data) {
                    $builder->where('organization_id', $context['organization_id'])
                        ->where('year', (int) ($data['year'] ?? now()->year));
                })
                ->exists();

            if ($duplicateOfficers) {
                throw ValidationException::withMessages([
                    'officer_ids' => __('messages.officer_already_assigned'),
                ]);
            }
        }

        if (! $isInternal && $officerIds->isNotEmpty()) {
            $neighborhoodId = $payload['neighborhood_association_id'] ?? null;
            $duplicateOfficers = Distribution::query()
                ->where('organization_id', $context['organization_id'])
                ->where('year', (int) ($payload['year'] ?? now()->year))
                ->where('id', '!=', $distribution->id)
                ->when($neighborhoodId, fn (Builder $builder) => $builder->where('neighborhood_association_id', '!=', $neighborhoodId))
                ->whereHas('officers', fn (Builder $builder) => $builder->whereIn('officer_id', $officerIds))
                ->exists();

            if ($duplicateOfficers) {
                throw ValidationException::withMessages([
                    'officer_ids' => __('messages.officer_already_assigned'),
                ]);
            }
        }

        if ($recipientIds->isNotEmpty() && ! $useManual) {
            $neighborhoodId = $payload['neighborhood_association_id'] ?? null;
            $duplicateRecipients = DistributionRecipient::query()
                ->whereIn('resident_id', $recipientIds)
                ->where('distribution_id', '!=', $distribution->id)
                ->where('distribution_class_id', '!=', $distributionClass->id)
                ->whereHas('distribution', function (Builder $builder) use ($context, $payload, $neighborhoodId) {
                    $builder->where('organization_id', $context['organization_id'])
                        ->where('year', (int) ($payload['year'] ?? now()->year));

                    if ($neighborhoodId) {
                        $builder->where('neighborhood_association_id', $neighborhoodId);
                    }
                })
                ->exists();

            if ($duplicateRecipients) {
                throw ValidationException::withMessages([
                    'recipient_ids' => __('messages.recipient_already_assigned'),
                ]);
            }
        }

        return DB::transaction(function () use ($distribution, $payload, $officerIds, $recipientIds, $manualRecipients, $distributionClass, $isInternal) {
            $distribution->update($payload);

            DB::table('distribution_officers')->where('distribution_id', $distribution->id)->delete();
            DB::table('distribution_recipients')->where('distribution_id', $distribution->id)->delete();

            if ($officerIds->isNotEmpty() && ! $isInternal) {
                $rows = $officerIds->map(fn ($id) => [
                    'distribution_id' => $distribution->id,
                    'officer_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_officers')->insert($rows->all());
            }

            if ($recipientIds->isNotEmpty()) {
                $rows = $recipientIds->map(fn ($id) => [
                    'distribution_id' => $distribution->id,
                    'resident_id' => $id,
                    'distribution_class_id' => $distributionClass->id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_recipients')->insert($rows->all());
            }

            if ($manualRecipients->isNotEmpty()) {
                $rows = $manualRecipients->map(fn ($item) => [
                    'distribution_id' => $distribution->id,
                    'resident_id' => null,
                    'recipient_name' => $item['name'] ?? null,
                    'recipient_phone' => $item['phone'] ?? null,
                    'recipient_address' => $item['address'] ?? null,
                    'distribution_class_id' => $distributionClass->id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_recipients')->insert($rows->all());
            }

            if ($isInternal && $officerIds->isNotEmpty()) {
                $officers = User::query()
                    ->whereIn('id', $officerIds)
                    ->get(['id', 'name'])
                    ->keyBy('id');

                $rows = $officerIds->map(function ($id) use ($distribution, $distributionClass, $officers) {
                    return [
                        'distribution_id' => $distribution->id,
                        'resident_id' => null,
                        'officer_id' => $id,
                        'recipient_name' => $officers->get($id)?->name,
                        'group_label' => null,
                        'amount_money' => $distributionClass->get_money,
                        'amount_rice' => $distributionClass->get_rice,
                        'distribution_class_id' => $distributionClass->id,
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
                DB::table('distribution_recipients')->insert($rows->all());
            }

            activity(__('messages.distributions'))
                ->causedBy(auth()->user())
                ->performedOn($distribution)
                ->log(__('messages.distributions_has_been_updated', ['name' => $distribution->title ?? '#' . $distribution->id]));

            return $distribution;
        });
    }

    public function residents(array $data): array
    {
        $context = $this->partnerContext();
        $assignedMap = collect();
        $year = isset($data['year']) ? (int) $data['year'] : null;
        $neighborhoodId = $data['neighborhood_association_id'] ?? null;

        if ($year && $neighborhoodId && ! empty($context['organization_id'])) {
            $assignedQuery = DistributionRecipient::query()
                ->select('distribution_recipients.resident_id', 'm_distribution_class_sources.name as class_name', 'distribution_recipients.distribution_class_id')
                ->join('distributions', 'distributions.id', '=', 'distribution_recipients.distribution_id')
                ->leftJoin('distribution_classes', 'distribution_classes.id', '=', 'distribution_recipients.distribution_class_id')
                ->leftJoin('m_distribution_class_sources', 'm_distribution_class_sources.id', '=', 'distribution_classes.distribution_class_source_id')
                ->where('distributions.organization_id', $context['organization_id'])
                ->where('distributions.year', $year)
                ->where('distributions.neighborhood_association_id', $neighborhoodId);

            if (! empty($data['distribution_id'])) {
                $assignedQuery->where('distribution_recipients.distribution_id', '!=', (int) $data['distribution_id']);
            }

            $assignedMap = $assignedQuery->get()->mapWithKeys(function ($row) {
                return [
                    (int) $row->resident_id => [
                        'class_name' => $row->class_name,
                        'class_id' => (int) ($row->distribution_class_id ?? 0),
                    ],
                ];
            });
        }

        $query = User::query()
            ->select('users.id', 'users.name')
            ->whereHas('residentProfile', function (Builder $profile) use ($data) {
                foreach (Arr::only($data, [
                    'country_id',
                    'province_id',
                    'city_id',
                    'district_id',
                    'village_id',
                    'citizens_association_id',
                    'neighborhood_association_id',
                ]) as $field => $value) {
                    if ($value) {
                        $profile->where($field, $value);
                    }
                }
            })
            ->with(['residentProfile.neighborhoodAssociation', 'residentProfile.citizensAssociation'])
            ->orderBy('users.name');

        if (! empty($data['search'])) {
            $query->where('users.name', 'like', '%' . $data['search'] . '%');
        }

        $selectedClassId = isset($data['distribution_class_id']) ? (int) $data['distribution_class_id'] : null;

        return $query->limit(200)->get()->map(function (User $user) use ($assignedMap, $selectedClassId) {
            $profile = $user->residentProfile;
            $rt = $profile?->neighborhoodAssociation?->number;
            $rw = $profile?->citizensAssociation?->number;
            $assigned = $assignedMap->get((int) $user->id);
            $assignedClassId = $assigned['class_id'] ?? null;
            $assignedName = $assigned['class_name'] ?? null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'rt' => $rt,
                'rw' => $rw,
                'address' => $profile?->address_line,
                'assigned_class_id' => $assignedClassId,
                'assigned_class_name' => $assignedName,
                'disabled' => $assignedClassId ? ($selectedClassId ? $assignedClassId !== $selectedClassId : true) : false,
            ];
        })->toArray();
    }

    public function formPayload(?Distribution $distribution = null): array
    {
        $context = $this->partnerContext() ?? [
            'organization_id' => null,
            'organization_name' => null,
        ];
        $organization = $context['organization_id']
            ? Organization::query()->find($context['organization_id'])
            : null;

        $year = (int) ($distribution?->year ?? now()->year);
        $distributionClassId = $distribution
            ? $distribution->recipients()->whereNotNull('distribution_class_id')->value('distribution_class_id')
            : null;
        $distributionClass = $distributionClassId
            ? DistributionClass::query()->find($distributionClassId)
            : null;
        $manualRecipients = $distribution
            ? $distribution->recipients()
                ->whereNull('resident_id')
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->recipient_name,
                    'phone' => $item->recipient_phone,
                    'address' => $item->recipient_address,
                ])
                ->values()
                ->toArray()
            : [];
        $residentIds = $distribution
            ? $distribution->recipients()
                ->whereNotNull('resident_id')
                ->pluck('resident_id')
                ->values()
                ->toArray()
            : [];
        $officerIds = $distribution
            ? ($distributionClass?->is_internal
                ? $distribution->recipients()->pluck('officer_id')->filter()->values()->toArray()
                : $distribution->officers()->pluck('officer_id')->values()->toArray())
            : [];
        $useManual = $distribution ? (! empty($manualRecipients) && empty($residentIds)) : false;

        $classes = DistributionClass::query()
            ->with('source')
            ->where('year', $year)
            ->when($context['organization_id'], fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->source?->name ?? '-',
                'year' => $item->year,
                'get_money' => $item->get_money,
                'get_rice' => $item->get_rice,
                'is_internal' => (bool) $item->is_internal,
            ])
            ->values()
            ->toArray();

        $officers = OrganizationUser::query()
            ->with(['user.mosqueProfile'])
            ->where('organization_id', $context['organization_id'])
            ->where('level_slug', 'like', 'mosque-%')
            ->orderBy('id')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->user_id,
                'name' => $member->user?->name,
                'level_slug' => $member->level_slug,
                'position' => $member->user?->mosqueProfile?->position,
            ])
            ->filter(fn ($item) => ! empty($item['id']))
            ->values()
            ->toArray();

        $countries = Country::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        return [
            'form' => [
                'id' => $distribution?->id,
                'distribution_class_id' => $distributionClassId,
                'year' => $year,
                'country_id' => $distribution?->country_id ?? $organization?->country_id,
                'province_id' => $distribution?->province_id ?? $organization?->province_id,
                'city_id' => $distribution?->city_id ?? $organization?->city_id,
                'district_id' => $distribution?->district_id ?? $organization?->district_id,
                'village_id' => $distribution?->village_id ?? $organization?->village_id,
                'citizens_association_id' => $distribution?->citizens_association_id ?? $organization?->citizens_association_id,
                'neighborhood_association_id' => $distribution?->neighborhood_association_id ?? $organization?->neighborhood_association_id,
                'use_manual_recipients' => $useManual,
                'recipient_ids' => $residentIds,
                'manual_recipients' => $manualRecipients,
                'officer_ids' => $officerIds,
            ],
            'routes' => [
                'store' => route('mosque.charity-distributions.store'),
                'update' => $distribution ? route('mosque.charity-distributions.update', $distribution) : null,
                'form' => $distribution ? route('mosque.charity-distributions.form', $distribution) : null,
                'residents' => route('mosque.charity-distributions.residents'),
                'locations' => [
                    'provinces' => route('ajax.locations.provinces'),
                    'cities' => route('ajax.locations.cities'),
                    'districts' => route('ajax.locations.districts'),
                    'villages' => route('ajax.locations.villages'),
                    'citizens' => route('ajax.locations.citizens'),
                    'neighborhoods' => route('ajax.locations.neighborhoods'),
                ],
            ],
            'options' => [
                'distribution_classes' => $classes,
                'officers' => $officers,
                'countries' => $countries,
            ],
            'context' => [
                'organization_id' => $context['organization_id'],
                'organization_name' => $context['organization_name'],
            ],
            'ui' => [
                'modal' => true,
                'mode' => $distribution ? 'edit' : 'create',
            ],
            'labels' => [
                'advanced_location' => __('messages.advanced_location'),
                'hide' => __('messages.hide'),
                'search' => __('messages.search'),
                'liter' => __('messages.liter'),
            ],
        ];
    }

    public function authorizeDistribution(Distribution $distribution): void
    {
        if ($this->canViewAllDistributions()) {
            return;
        }

        $assigned = $distribution->officers()
            ->where('officer_id', auth()->id())
            ->exists();

        if (! $assigned) {
            abort(403);
        }
    }

    public function canViewAllDistributions(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $membership = $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'mosque-%')
            ->first();

        if (! $membership) {
            return false;
        }

        return str_contains($membership->level_slug, 'superadmin');
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
            ->first();

        if (! $membership) {
            return null;
        }

        return [
            'organization_id' => $membership->organization_id,
            'organization_name' => $membership->organization?->name,
        ];
    }

    public function distributionSummary(): array
    {
        return $this->distributionSummaryWithFilters();
    }

    public function distributionSummaryWithFilters(
        ?int $distributionClassId = null,
        ?int $year = null,
        ?int $neighborhoodId = null
    ): array {
        $context = $this->partnerContext();
        $currency = strtoupper((string) config('money.defaultCurrency', 'IDR'));
        $canViewAll = $this->canViewAllDistributions();

        $zakatTypeId = DistributionType::query()
            ->where('slug', 'zakat')
            ->value('id');

        $query = Distribution::query()
            ->withCount([
                'recipients',
                'recipients as distributed_count' => fn (Builder $builder) => $builder->where('status', 'distributed'),
                'recipients as failed_count' => fn (Builder $builder) => $builder->whereIn('status', ['failed', 'redirected']),
            ]);

        if (! empty($context['organization_id'])) {
            $query->where('distributions.organization_id', $context['organization_id']);
        }

        if ($zakatTypeId) {
            $query->where('distribution_type_id', $zakatTypeId);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if ($neighborhoodId) {
            $query->where('neighborhood_association_id', $neighborhoodId);
        }

        if ($distributionClassId) {
            $query->whereHas('recipients', fn (Builder $builder) => $builder->where('distribution_class_id', $distributionClassId));
        }

        if (! $canViewAll) {
            $query->whereHas('officers', fn (Builder $builder) => $builder->where('officer_id', auth()->id()));
        }

        $totalMoney = 0.0;
        $distributedMoney = 0.0;
        $totalRice = 0.0;
        $distributedRice = 0.0;
        $totalRecipients = 0;
        $distributedRecipients = 0;
        $failedRecipients = 0;

        $distributions = $query->get();
        $distributionIds = $distributions->pluck('id')->filter()->values()->all();

        $classMap = DistributionRecipient::query()
            ->whereIn('distribution_id', $distributionIds)
            ->whereNotNull('distribution_class_id')
            ->select('distribution_id', 'distribution_class_id')
            ->groupBy('distribution_id', 'distribution_class_id')
            ->pluck('distribution_class_id', 'distribution_id');

        $classIds = $classMap->values()->unique()->filter()->values()->all();
        $classes = DistributionClass::query()
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        foreach ($distributions as $distribution) {
            $classId = $classMap[$distribution->id] ?? null;
            $class = $classId ? $classes->get($classId) : null;
            $moneyPer = (float) ($class?->get_money ?? 0);
            $ricePer = (float) ($class?->get_rice ?? 0);
            $recipients = (int) $distribution->recipients_count;
            $distributed = (int) $distribution->distributed_count;

            $totalRecipients += $recipients;
            $distributedRecipients += $distributed;
            $failedRecipients += (int) $distribution->failed_count;

            $totalMoney += $moneyPer * $recipients;
            $distributedMoney += $moneyPer * $distributed;
            $totalRice += $ricePer * $recipients;
            $distributedRice += $ricePer * $distributed;
        }

        $formatMoney = function (float $amount) use ($currency) {
            try {
                return \Cknow\Money\Money::{$currency}($amount)->format(app()->getLocale());
            } catch (\Throwable $exception) {
                return \Cknow\Money\Money::IDR($amount)->format(app()->getLocale());
            }
        };

        $formatDecimal = fn (float $value) => \Illuminate\Support\Number::format($value, 2, 2, app()->getLocale());

        $incomeTotals = $this->incomeTotalsForRiceTypes($year, $context['organization_id'] ?? null);
        $incomeMoney = (float) ($incomeTotals['total_money'] ?? 0);
        $incomeRice = (float) ($incomeTotals['total_rice'] ?? 0);
        $shortageMoney = max($totalMoney - $incomeMoney, 0);
        $shortageRice = max($totalRice - $incomeRice, 0);
        $shortagePerRecipient = $totalRecipients > 0 ? ($shortageMoney / $totalRecipients) : 0;
        $adjustYear = $year ?: (int) now()->year;
        $suggestedAdjustments = [];
        $notDistributedRecipients = [];
        $notDistributedTotal = 0;

        if ($shortagePerRecipient > 0) {
            $adjustClasses = DistributionClass::query()
                ->with('source')
                ->when($context['organization_id'] ?? null, fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
                ->where('year', $adjustYear)
                ->when($distributionClassId, fn (Builder $query) => $query->where('id', $distributionClassId))
                ->orderBy('id')
                ->get();

            $suggestedAdjustments = $adjustClasses->map(function (DistributionClass $class) use ($shortagePerRecipient, $formatMoney) {
                $current = (float) ($class->get_money ?? 0);
                $suggested = max($current - $shortagePerRecipient, 0);

                return [
                    'id' => $class->id,
                    'name' => $class->source?->name ?? '-',
                    'current_money' => $current,
                    'current_money_label' => $formatMoney($current),
                    'suggested_money' => $suggested,
                    'suggested_money_label' => $formatMoney($suggested),
                ];
            })->values()->toArray();
        }

        $notDistributedQuery = DistributionRecipientStatusLog::query()
            ->where('to_status', 'failed')
            ->whereHas('recipient', function (Builder $builder) use ($distributionClassId, $year, $neighborhoodId) {
                $builder->whereIn('status', ['failed', 'redirected']);

                if ($distributionClassId) {
                    $builder->where('distribution_class_id', $distributionClassId);
                }

                $builder->whereHas('distribution', function (Builder $sub) use ($year, $neighborhoodId) {
                    if ($year) {
                        $sub->where('year', $year);
                    }

                    if ($neighborhoodId) {
                        $sub->where('neighborhood_association_id', $neighborhoodId);
                    }
                });
            })
            ->whereHas('recipient.distribution', function (Builder $builder) use ($context, $canViewAll) {
                if (! empty($context['organization_id'])) {
                    $builder->where('organization_id', $context['organization_id']);
                }

                if (! $canViewAll) {
                    $builder->whereHas('officers', fn (Builder $query) => $query->where('officer_id', auth()->id()));
                }
            })
            ->with(['recipient.resident', 'recipient.distributionClass.source', 'recipient.distribution'])
            ->orderByDesc('created_at');

        $notDistributedLogs = $notDistributedQuery->get()
            ->groupBy('distribution_recipient_id')
            ->map(fn ($logs) => $logs->first())
            ->values();

        $notDistributedTotal = $notDistributedLogs->count();

        $notDistributedRecipients = $notDistributedLogs->map(function (DistributionRecipientStatusLog $log) {
            $recipient = $log->recipient;
            $distribution = $recipient?->distribution;
            $className = $recipient?->distributionClass?->source?->name;

            return [
                'id' => $recipient?->id,
                'name' => $recipient?->resident?->name ?? $recipient?->recipient_name ?? '-',
                'class_name' => $className ?? '-',
                'status' => $recipient?->status ?? '-',
                'status_label' => $recipient?->status ? __('messages.' . $recipient->status) : '-',
                'status_reason' => $log->status_reason ? __('messages.' . $log->status_reason) : '-',
                'status_note' => $log->status_note ?? '-',
                'rt' => $distribution?->neighborhoodAssociation?->number,
                'rw' => $distribution?->citizensAssociation?->number,
            ];
        })->values()->toArray();

        return [
            'total_recipients' => $totalRecipients,
            'distributed_recipients' => $distributedRecipients,
            'failed_recipients' => $failedRecipients,
            'pending_recipients' => max($totalRecipients - $distributedRecipients - $failedRecipients, 0),
            'total_money' => $totalMoney,
            'distributed_money' => $distributedMoney,
            'total_money_label' => $formatMoney($totalMoney),
            'distributed_money_label' => $formatMoney($distributedMoney),
            'total_rice' => $totalRice,
            'distributed_rice' => $distributedRice,
            'total_rice_label' => $formatDecimal($totalRice),
            'distributed_rice_label' => $formatDecimal($distributedRice),
            'remaining_money' => max($totalMoney - $distributedMoney, 0),
            'remaining_money_label' => $formatMoney(max($totalMoney - $distributedMoney, 0)),
            'remaining_rice' => max($totalRice - $distributedRice, 0),
            'remaining_rice_label' => $formatDecimal(max($totalRice - $distributedRice, 0)),
            'income_money' => $incomeMoney,
            'income_money_label' => $formatMoney($incomeMoney),
            'income_rice' => $incomeRice,
            'income_rice_label' => $formatDecimal($incomeRice),
            'shortage_money' => $shortageMoney,
            'shortage_money_label' => $formatMoney($shortageMoney),
            'shortage_rice' => $shortageRice,
            'shortage_rice_label' => $formatDecimal($shortageRice),
            'shortage_money_per_recipient' => $shortagePerRecipient,
            'shortage_money_per_recipient_label' => $formatMoney($shortagePerRecipient),
            'suggested_adjustments' => $suggestedAdjustments,
            'not_distributed_recipients' => $notDistributedRecipients,
            'not_distributed_total' => $notDistributedTotal,
        ];
    }

    public function summaryViewPayload(): array
    {
        $context = $this->partnerContext();
        $currentYear = (int) now()->year;

        $classes = DistributionClass::query()
            ->with('source')
            ->when($context['organization_id'] ?? null, fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->source?->name ?? '-',
                'label' => ($item->source?->name ?? '-') . ' (' . $item->year . ')',
                'source_name' => $item->source?->name ?? '-',
                'year' => $item->year,
                'money_per_person' => (float) ($item->get_money ?? 0),
                'rice_per_person' => (float) ($item->get_rice ?? 0),
            ])
            ->values()
            ->toArray();

        $years = Distribution::query()
            ->select('year')
            ->when($context['organization_id'] ?? null, fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values()
            ->toArray();

        $neighborhoodIds = Distribution::query()
            ->when($context['organization_id'] ?? null, fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->whereNotNull('neighborhood_association_id')
            ->pluck('neighborhood_association_id')
            ->unique()
            ->values()
            ->toArray();

        $neighborhoods = NeighborhoodAssociation::query()
            ->when(! empty($neighborhoodIds), fn (Builder $query) => $query->whereIn('id', $neighborhoodIds))
            ->orderBy('number')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name ?? ('RT ' . $item->number),
            ])
            ->values()
            ->toArray();

        return [
            'filters' => [
                'distribution_class_id' => '',
                'year' => $currentYear,
                'neighborhood_association_id' => '',
            ],
            'options' => [
                'distribution_classes' => $classes,
                'years' => $years,
                'neighborhoods' => $neighborhoods,
            ],
            'routes' => [
                'summary' => route('mosque.charity-distributions.summary'),
                'fund_sources' => route('mosque.charity-distributions.fund-sources'),
                'fund_sources_store' => route('mosque.charity-distributions.fund-sources.store'),
                'fund_sources_delete' => route('mosque.charity-distributions.fund-sources.delete', ['fundSource' => '__source__']),
            ],
            'summary' => $this->distributionSummaryWithFilters(null, $currentYear, null),
        ];
    }


    public function fundSourcesPayload(?int $distributionClassId, ?int $year, ?int $neighborhoodId): array
    {
        $context = $this->partnerContext();
        $year = (int) ($year ?: now()->year);

        $types = CharityType::query()
            ->with('source')
            ->where('year', $year)
            ->when($context['organization_id'] ?? null, fn (Builder $query) => $query->where('organization_id', $context['organization_id']))
            ->orderBy('id', 'desc')
            ->get();

        $totalsByType = $types->mapWithKeys(function (CharityType $type) use ($context) {
            $query = CharityTransaction::query()
                ->forOrganization($context['organization_id'] ?? null)
                ->forCharityType($type->id)
                ->paid()
                ->createdInYear((int) $type->year);

            $totals = app(CharityReceiptTotalsService::class)->totalsForQuery($query);

            return [
                $type->id => [
                    'total_money' => $totals['total_money'],
                    'total_rice' => $totals['total_rice'],
                ],
            ];
        });

        $requiredTotals = $this->distributionRequiredTotalsForFilters($distributionClassId, $year, $neighborhoodId);

        $distributionTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');

        $allUsedByType = DistributionFundSource::query()
            ->where('source_type', 'charity')
            ->when($context['organization_id'] ?? null, fn (Builder $builder) => $builder->where('organization_id', $context['organization_id']))
            ->where('year', $year)
            ->when($distributionTypeId, fn (Builder $builder) => $builder->where('distribution_type_id', $distributionTypeId))
            ->select('charity_type_id', DB::raw('SUM(amount_used) as total_used'))
            ->groupBy('charity_type_id')
            ->pluck('total_used', 'charity_type_id');

        $allUsedByTypeRice = DistributionFundSource::query()
            ->where('source_type', 'charity')
            ->when($context['organization_id'] ?? null, fn (Builder $builder) => $builder->where('organization_id', $context['organization_id']))
            ->where('year', $year)
            ->when($distributionTypeId, fn (Builder $builder) => $builder->where('distribution_type_id', $distributionTypeId))
            ->select('charity_type_id', DB::raw('SUM(amount_used_rice) as total_used'))
            ->groupBy('charity_type_id')
            ->pluck('total_used', 'charity_type_id');

        $currentUsedByType = $this->applyFundSourceFilters(
            DistributionFundSource::query()->where('source_type', 'charity'),
            $distributionClassId,
            $year,
            $neighborhoodId
        )
            ->select('charity_type_id', DB::raw('SUM(amount_used) as total_used'))
            ->groupBy('charity_type_id')
            ->pluck('total_used', 'charity_type_id');

        $currentUsedByTypeRice = $this->applyFundSourceFilters(
            DistributionFundSource::query()->where('source_type', 'charity'),
            $distributionClassId,
            $year,
            $neighborhoodId
        )
            ->select('charity_type_id', DB::raw('SUM(amount_used_rice) as total_used'))
            ->groupBy('charity_type_id')
            ->pluck('total_used', 'charity_type_id');

        $usedByType = $allUsedByType->mapWithKeys(fn ($value, $key) => [
            $key => max((float) $value - (float) ($currentUsedByType[$key] ?? 0), 0),
        ]);

        $usedByTypeRice = $allUsedByTypeRice->mapWithKeys(fn ($value, $key) => [
            $key => max((float) $value - (float) ($currentUsedByTypeRice[$key] ?? 0), 0),
        ]);

        $remainingByType = $types->mapWithKeys(function (CharityType $type) use ($totalsByType, $usedByType) {
            $totalMoney = (float) ($totalsByType[$type->id]['total_money'] ?? 0);
            $usedMoney = (float) ($usedByType[$type->id] ?? 0);
            $remaining = max($totalMoney - $usedMoney, 0);

            return [
                $type->id => $remaining,
            ];
        });

        $remainingByTypeRice = $types->mapWithKeys(function (CharityType $type) use ($totalsByType, $usedByTypeRice) {
            $totalRice = (float) ($totalsByType[$type->id]['total_rice'] ?? 0);
            $usedRice = (float) ($usedByTypeRice[$type->id] ?? 0);
            $remaining = max($totalRice - $usedRice, 0);

            return [
                $type->id => $remaining,
            ];
        });

        $setting = $this->resolveFundSetting($distributionClassId, $year, $neighborhoodId);
        $priorityIds = $setting?->priority_charity_type_ids ?? $this->defaultPriorityTypeIds($types);
        $priorityIds = collect($priorityIds)->filter()->values()->toArray();
        $enforcePriority = $setting?->enforce_priority ?? ! empty($priorityIds);

        $fundSources = $this->applyFundSourceFilters(
            DistributionFundSource::query(),
            $distributionClassId,
            $year,
            $neighborhoodId
        )
            ->with('charityType.source')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (DistributionFundSource $source) use ($remainingByType, $remainingByTypeRice) {
                $remaining = null;
                $remainingRice = null;
                if ($source->source_type === 'charity') {
                    $available = (float) ($remainingByType[$source->charity_type_id] ?? 0);
                    $remaining = max($available - (float) $source->amount_used, 0);
                    $availableRice = (float) ($remainingByTypeRice[$source->charity_type_id] ?? 0);
                    $remainingRice = max($availableRice - (float) $source->amount_used_rice, 0);
                }

                return [
                    'id' => $source->id,
                    'source_type' => $source->source_type,
                    'charity_type_id' => $source->charity_type_id,
                    'source_name' => $source->source_name ?? $source->charityType?->source?->name,
                    'amount_used' => (float) $source->amount_used,
                    'amount_used_label' => $this->formatMoney((float) $source->amount_used),
                    'amount_used_rice' => (float) $source->amount_used_rice,
                    'amount_used_rice_label' => $this->formatDecimal((float) $source->amount_used_rice),
                    'remaining_amount' => $remaining,
                    'remaining_amount_label' => $remaining === null ? '-' : $this->formatMoney($remaining),
                    'remaining_rice' => $remainingRice,
                    'remaining_rice_label' => $remainingRice === null ? '-' : $this->formatDecimal($remainingRice),
                    'notes' => $source->notes,
                ];
            })
            ->values()
            ->toArray();

        $selectedIds = collect($fundSources)
            ->where('source_type', 'charity')
            ->pluck('charity_type_id')
            ->filter()
            ->values()
            ->toArray();

        $otherSource = collect($fundSources)
            ->where('source_type', 'other')
            ->groupBy('source_name')
            ->map(fn ($items) => [
                'source_name' => $items->first()['source_name'] ?? null,
                'total_amount' => collect($items)->sum('amount_used'),
                'total_rice' => collect($items)->sum('amount_used_rice'),
            ])
            ->first();

        $totalUsed = collect($fundSources)->sum('amount_used');
        $totalUsedRice = collect($fundSources)->sum('amount_used_rice');

        return [
            'fund_sources' => $fundSources,
            'options' => [
                'charity_types' => $types->map(function (CharityType $type) use ($totalsByType, $usedByType, $usedByTypeRice) {
                    $totalMoney = (float) ($totalsByType[$type->id]['total_money'] ?? 0);
                    $usedMoney = (float) ($usedByType[$type->id] ?? 0);
                    $remaining = max($totalMoney - $usedMoney, 0);
                    $totalRice = (float) ($totalsByType[$type->id]['total_rice'] ?? 0);
                    $usedRice = (float) ($usedByTypeRice[$type->id] ?? 0);
                    $remainingRice = max($totalRice - $usedRice, 0);

                    return [
                        'id' => $type->id,
                        'name' => ($type->source?->name ?? '-') . ' (' . $type->year . ')',
                        'total_money' => $totalMoney,
                        'total_money_label' => $this->formatMoney($totalMoney),
                        'used_money' => $usedMoney,
                        'used_money_label' => $this->formatMoney($usedMoney),
                        'remaining_money' => $remaining,
                        'remaining_money_label' => $this->formatMoney($remaining),
                        'total_rice' => $totalRice,
                        'total_rice_label' => $this->formatDecimal($totalRice),
                        'used_rice' => $usedRice,
                        'used_rice_label' => $this->formatDecimal($usedRice),
                        'remaining_rice' => $remainingRice,
                        'remaining_rice_label' => $this->formatDecimal($remainingRice),
                    ];
                })->values()->toArray(),
            ],
            'selection' => [
                'charity_type_ids' => $selectedIds,
                'priority_charity_type_ids' => $priorityIds,
                'enforce_priority' => $enforcePriority,
                'other_source_name' => is_array($otherSource) ? ($otherSource['source_name'] ?? null) : null,
                'other_source_amount' => is_array($otherSource) ? (float) ($otherSource['total_amount'] ?? 0) : 0,
                'other_source_rice' => is_array($otherSource) ? (float) ($otherSource['total_rice'] ?? 0) : 0,
            ],
            'summary' => [
                'required_money' => $requiredTotals['total_money'],
                'required_money_label' => $this->formatMoney($requiredTotals['total_money']),
                'required_rice' => $requiredTotals['total_rice'],
                'required_rice_label' => $this->formatDecimal($requiredTotals['total_rice']),
                'used_money' => $totalUsed,
                'used_money_label' => $this->formatMoney($totalUsed),
                'used_rice' => $totalUsedRice,
                'used_rice_label' => $this->formatDecimal($totalUsedRice),
                'remaining_money' => max(0, $requiredTotals['total_money'] - $totalUsed),
                'remaining_money_label' => $this->formatMoney(max(0, $requiredTotals['total_money'] - $totalUsed)),
                'remaining_rice' => max(0, $requiredTotals['total_rice'] - $totalUsedRice),
                'remaining_rice_label' => $this->formatDecimal(max(0, $requiredTotals['total_rice'] - $totalUsedRice)),
            ],
        ];
    }

    public function storeFundSource(?int $distributionClassId, ?int $year, ?int $neighborhoodId, array $data): ?DistributionFundSource
    {
        $context = $this->partnerContext();
        if (! $context) {
            abort(403);
        }

        $year = (int) ($year ?: now()->year);

        $charityTypeIds = collect($data['charity_type_ids'] ?? [])
            ->filter()
            ->values()
            ->toArray();
        $priorityIds = collect($data['priority_charity_type_ids'] ?? [])
            ->filter()
            ->values()
            ->toArray();
        $enforcePriority = (bool) ($data['enforce_priority'] ?? ! empty($priorityIds));

        if ($enforcePriority && ! empty($priorityIds)) {
            $charityTypeIds = collect(array_merge($priorityIds, $charityTypeIds))
                ->unique()
                ->values()
                ->toArray();
        }
        $otherName = $data['other_source_name'] ?? null;
        $otherAmount = (float) ($data['other_source_amount'] ?? 0);
        $otherRice = (float) ($data['other_source_rice'] ?? 0);

        if (empty($charityTypeIds) && $otherAmount <= 0 && $otherRice <= 0) {
            throw ValidationException::withMessages([
                'charity_type_ids' => __('validation.required', ['attribute' => __('messages.charity_type')]),
            ]);
        }

        if (($otherAmount > 0 || $otherRice > 0) && empty($otherName)) {
            throw ValidationException::withMessages([
                'other_source_name' => __('validation.required', ['attribute' => __('messages.source_name')]),
            ]);
        }

        $payload = $this->fundSourcesPayload($distributionClassId, $year, $neighborhoodId);
        $types = collect($payload['options']['charity_types'] ?? []);
        $availableTypeIds = $types->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $charityTypeIds = collect($charityTypeIds)->filter(fn ($id) => in_array((int) $id, $availableTypeIds, true))->values()->toArray();
        $priorityIds = collect($priorityIds)->filter(fn ($id) => in_array((int) $id, $availableTypeIds, true))->values()->toArray();
        $requiredMoney = (float) ($payload['summary']['required_money'] ?? 0);
        $requiredRice = (float) ($payload['summary']['required_rice'] ?? 0);

        $availableMoneyMap = $types->mapWithKeys(fn ($item) => [
            (int) $item['id'] => (float) ($item['remaining_money'] ?? 0),
        ]);
        $availableRiceMap = $types->mapWithKeys(fn ($item) => [
            (int) $item['id'] => (float) ($item['remaining_rice'] ?? 0),
        ]);

        $remainingMoney = $requiredMoney;
        $remainingRice = $requiredRice;
        $rows = [];
        $distributionTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');

        foreach ($charityTypeIds as $typeId) {
            $availableMoney = (float) ($availableMoneyMap[$typeId] ?? 0);
            $availableRice = (float) ($availableRiceMap[$typeId] ?? 0);
            $useMoney = $remainingMoney > 0 ? min($availableMoney, $remainingMoney) : 0;
            $useRice = $remainingRice > 0 ? min($availableRice, $remainingRice) : 0;
            if ($useMoney <= 0 && $useRice <= 0) {
                continue;
            }
            $rows[] = [
                'distribution_id' => null,
                'organization_id' => $context['organization_id'] ?? null,
                'distribution_type_id' => $distributionTypeId,
                'distribution_class_id' => $distributionClassId,
                'neighborhood_association_id' => $neighborhoodId,
                'year' => $year,
                'source_type' => 'charity',
                'charity_type_id' => $typeId,
                'source_name' => null,
                'amount_used' => $useMoney,
                'amount_used_rice' => $useRice,
                'notes' => null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $remainingMoney -= $useMoney;
            $remainingRice -= $useRice;
        }

        if ($remainingMoney > 0 && $otherAmount > 0) {
            $use = min($otherAmount, $remainingMoney);
            $rows[] = [
                'distribution_id' => null,
                'organization_id' => $context['organization_id'] ?? null,
                'distribution_type_id' => $distributionTypeId,
                'distribution_class_id' => $distributionClassId,
                'neighborhood_association_id' => $neighborhoodId,
                'year' => $year,
                'source_type' => 'other',
                'charity_type_id' => null,
                'source_name' => $otherName,
                'amount_used' => $use,
                'amount_used_rice' => 0,
                'notes' => null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $remainingMoney -= $use;
        }

        if ($remainingRice > 0 && $otherRice > 0) {
            $useRice = min($otherRice, $remainingRice);
            $rows[] = [
                'distribution_id' => null,
                'organization_id' => $context['organization_id'] ?? null,
                'distribution_type_id' => $distributionTypeId,
                'distribution_class_id' => $distributionClassId,
                'neighborhood_association_id' => $neighborhoodId,
                'year' => $year,
                'source_type' => 'other',
                'charity_type_id' => null,
                'source_name' => $otherName,
                'amount_used' => 0,
                'amount_used_rice' => $useRice,
                'notes' => null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $remainingRice -= $useRice;
        }

        if ($remainingMoney > 0) {
            throw ValidationException::withMessages([
                'other_source_amount' => __('messages.fund_sources_insufficient'),
            ]);
        }

        if ($remainingRice > 0) {
            throw ValidationException::withMessages([
                'other_source_rice' => __('messages.fund_sources_rice_insufficient'),
            ]);
        }

        DB::transaction(function () use ($distributionClassId, $year, $neighborhoodId, $rows, $priorityIds, $enforcePriority) {
            $this->applyFundSourceFilters(
                DistributionFundSource::query(),
                $distributionClassId,
                $year,
                $neighborhoodId
            )->delete();

            if (! empty($rows)) {
                DistributionFundSource::query()->insert($rows);
            }

            $this->storeFundSetting($distributionClassId, $year, $neighborhoodId, $priorityIds, $enforcePriority);
        });

        return DistributionFundSource::query()
            ->when($context['organization_id'] ?? null, fn (Builder $builder) => $builder->where('organization_id', $context['organization_id']))
            ->where('year', $year)
            ->when($distributionClassId, fn (Builder $builder) => $builder->where('distribution_class_id', $distributionClassId), fn (Builder $builder) => $builder->whereNull('distribution_class_id'))
            ->when($neighborhoodId, fn (Builder $builder) => $builder->where('neighborhood_association_id', $neighborhoodId), fn (Builder $builder) => $builder->whereNull('neighborhood_association_id'))
            ->first();
    }

    public function deleteFundSource(DistributionFundSource $fundSource): void
    {
        $context = $this->partnerContext();
        if (! $context || ($fundSource->organization_id && $fundSource->organization_id !== $context['organization_id'])) {
            abort(403);
        }
        $fundSource->delete();
    }

    protected function distributionRequiredTotalsForFilters(?int $distributionClassId, ?int $year, ?int $neighborhoodId): array
    {
        $summary = $this->distributionSummaryWithFilters($distributionClassId, $year, $neighborhoodId);

        return [
            'total_money' => (float) ($summary['total_money'] ?? 0),
            'total_rice' => (float) ($summary['total_rice'] ?? 0),
        ];
    }

    protected function applyFundSourceFilters(
        Builder $query,
        ?int $distributionClassId,
        ?int $year,
        ?int $neighborhoodId
    ): Builder {
        $context = $this->partnerContext();
        $year = (int) ($year ?: now()->year);
        $distributionTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');

        $query->when($context['organization_id'] ?? null, fn (Builder $builder) => $builder->where('organization_id', $context['organization_id']))
            ->where('year', $year);

        if ($distributionTypeId) {
            $query->where('distribution_type_id', $distributionTypeId);
        }

        if ($distributionClassId) {
            $query->where('distribution_class_id', $distributionClassId);
        } else {
            $query->whereNull('distribution_class_id');
        }

        if ($neighborhoodId) {
            $query->where('neighborhood_association_id', $neighborhoodId);
        } else {
            $query->whereNull('neighborhood_association_id');
        }

        return $query;
    }

    protected function resolveFundSetting(?int $distributionClassId, int $year, ?int $neighborhoodId): ?DistributionFundSetting
    {
        $context = $this->partnerContext();
        $distributionTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');

        return DistributionFundSetting::query()
            ->when($context['organization_id'] ?? null, fn (Builder $builder) => $builder->where('organization_id', $context['organization_id']))
            ->where('year', $year)
            ->when($distributionTypeId, fn (Builder $builder) => $builder->where('distribution_type_id', $distributionTypeId))
            ->when($distributionClassId, fn (Builder $builder) => $builder->where('distribution_class_id', $distributionClassId), fn (Builder $builder) => $builder->whereNull('distribution_class_id'))
            ->when($neighborhoodId, fn (Builder $builder) => $builder->where('neighborhood_association_id', $neighborhoodId), fn (Builder $builder) => $builder->whereNull('neighborhood_association_id'))
            ->first();
    }

    protected function storeFundSetting(?int $distributionClassId, int $year, ?int $neighborhoodId, array $priorityIds, bool $enforcePriority): DistributionFundSetting
    {
        $context = $this->partnerContext();
        $distributionTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');

        return DistributionFundSetting::updateOrCreate([
            'organization_id' => $context['organization_id'] ?? null,
            'distribution_type_id' => $distributionTypeId,
            'distribution_class_id' => $distributionClassId,
            'neighborhood_association_id' => $neighborhoodId,
            'year' => $year,
        ], [
            'priority_charity_type_ids' => array_values($priorityIds),
            'enforce_priority' => $enforcePriority,
            'created_by' => auth()->id(),
        ]);
    }

    protected function defaultPriorityTypeIds($types): array
    {
        return collect($types)
            ->filter(fn (CharityType $type) => in_array($type->source?->slug, ['zakat-fitrah', 'fidyah'], true))
            ->pluck('id')
            ->values()
            ->toArray();
    }

    protected function formatMoney(float $amount): string
    {
        $currency = strtoupper((string) config('money.defaultCurrency', 'IDR'));
        try {
            return \Cknow\Money\Money::{$currency}($amount)->format(app()->getLocale());
        } catch (\Throwable $exception) {
            return \Cknow\Money\Money::IDR($amount)->format(app()->getLocale());
        }
    }

    protected function incomeTotalsForRiceTypes(?int $year, ?int $organizationId): array
    {
        $year = (int) ($year ?: now()->year);

        $typeIds = CharityType::query()
            ->with('source')
            ->where('year', $year)
            ->when($organizationId, fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->whereHas('source', fn (Builder $query) => $query->whereIn('slug', ['zakat-fitrah', 'fidyah']))
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (empty($typeIds)) {
            return ['total_money' => 0.0, 'total_rice' => 0.0];
        }

        $query = CharityTransaction::query()
            ->forOrganization($organizationId)
            ->paid()
            ->createdInYear($year)
            ->whereIn('charity_type_id', $typeIds);

        return app(CharityReceiptTotalsService::class)->totalsForQuery($query);
    }

    protected function formatDecimal(float $value): string
    {
        return \Illuminate\Support\Number::format($value, 2, 2, app()->getLocale());
    }
}
