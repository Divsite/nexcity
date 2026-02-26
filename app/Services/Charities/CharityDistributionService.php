<?php

namespace App\Services\Charities;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionTypes\DistributionType;
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
        $recipientIds = collect($data['recipient_ids'] ?? [])->filter()->unique()->values();
        $manualRecipients = collect($data['manual_recipients'] ?? [])
            ->map(fn ($item) => Arr::only($item, ['name', 'phone', 'address']))
            ->filter(fn ($item) => ! empty($item['name']))
            ->values();

        return DB::transaction(function () use ($payload, $officerIds, $recipientIds, $manualRecipients, $distributionClass) {
            $distribution = Distribution::create($payload);

            if ($officerIds->isNotEmpty()) {
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('distribution_recipients')->insert($rows->all());
            }

            activity(__('messages.distributions'))
                ->causedBy(auth()->user())
                ->performedOn($distribution)
                ->log(__('messages.distributions_has_been_created', ['name' => $distribution->title ?? '#' . $distribution->id]));

            return $distribution;
        });
    }

    public function residents(array $data): array
    {
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

        return $query->limit(200)->get()->map(function (User $user) {
            $profile = $user->residentProfile;
            $rt = $profile?->neighborhoodAssociation?->number;
            $rw = $profile?->citizensAssociation?->number;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'rt' => $rt,
                'rw' => $rw,
                'address' => $profile?->address_line,
            ];
        })->toArray();
    }

    public function formPayload(): array
    {
        $context = $this->partnerContext();
        $organization = $context['organization_id']
            ? Organization::query()->find($context['organization_id'])
            : null;

        $year = (int) now()->year;

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
                'distribution_class_id' => null,
                'year' => $year,
                'country_id' => $organization?->country_id,
                'province_id' => $organization?->province_id,
                'city_id' => $organization?->city_id,
                'district_id' => $organization?->district_id,
                'village_id' => $organization?->village_id,
                'citizens_association_id' => $organization?->citizens_association_id,
                'neighborhood_association_id' => $organization?->neighborhood_association_id,
                'use_manual_recipients' => false,
                'recipient_ids' => [],
                'manual_recipients' => [],
                'officer_ids' => [],
            ],
            'routes' => [
                'store' => route('mosque.charity-distributions.store'),
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
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'mosque-%')
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
                'recipients as failed_count' => fn (Builder $builder) => $builder->where('status', 'failed'),
            ]);

        if (! empty($context['organization_id'])) {
            $query->where('distributions.organization_id', $context['organization_id']);
        }

        if ($zakatTypeId) {
            $query->where('distribution_type_id', $zakatTypeId);
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
        ];
    }
}
