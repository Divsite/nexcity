<?php

namespace App\Services\Charities;

use Cknow\Money\Money;
use App\Models\Charities\CharityAlmsReceipt;
use App\Models\Charities\CharityDonationReceipt;
use App\Models\Charities\CharityEndowmentReceipt;
use App\Models\Charities\CharityFidyaReceipt;
use App\Models\Charities\CharityFitrahReceipt;
use App\Models\Charities\CharityMalReceipt;
use App\Models\Charities\CharityTransaction;
use App\Models\Charities\CharityTransactionPayer;
use App\Models\CharityPayments\CharityPayment;
use App\Models\CharityTypes\CharityType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

class CharityTransactionService
{
    public function __construct(
        protected CharityReceiptTotalsService $receiptTotalsService
    ) {
    }
    public function create(array $data): CharityTransaction
    {
        return DB::transaction(function () use ($data) {
            [$payload, $detail, $packagePayers] = $this->prepareTransactionPayload($data);
            $transaction = CharityTransaction::create($payload);
            $this->syncDetail($transaction, $detail);
            $this->syncPackagePayers($transaction, $packagePayers);

            return $transaction;
        });
    }

    public function update(CharityTransaction $charityTransaction, array $data): CharityTransaction
    {
        return DB::transaction(function () use ($charityTransaction, $data) {
            [$payload, $detail, $packagePayers] = $this->prepareTransactionPayload($data, $charityTransaction);
            $charityTransaction->update($payload);
            $this->syncDetail($charityTransaction, $detail, true);
            $this->syncPackagePayers($charityTransaction, $packagePayers);

            return $charityTransaction;
        });
    }

    public function summaryPayload(
        ?int $typeId = null,
        ?int $yearTypeId = null,
        ?int $year = null,
        ?string $paymentMethod = null,
        ?string $yearPaymentMethod = null
    ): array
    {
        $summaryCards = $this->summaryCards($typeId, $paymentMethod);
        $yearSummary = $this->yearSummary($yearTypeId, $year, $yearPaymentMethod);

        return [
            'summary' => [
                'today' => $summaryCards['today'],
                'year' => $summaryCards['year'],
                'yearly' => $yearSummary,
            ],
        ];
    }

    public function summaryViewPayload(?array $context = null, ?int $year = null, ?string $summaryRoute = null): array
    {
        $targetYear = (int) ($year ?: now()->year);
        $charityTypes = $this->charityTypeOptions($context, $targetYear);
        $defaultTypeId = collect($charityTypes)
            ->firstWhere('slug', 'zakat-fitrah')['id']
            ?? ($charityTypes[0]['id'] ?? null);

        return array_merge([
            'filters' => [
                'type_id' => $defaultTypeId,
                'year_type_id' => $defaultTypeId,
                'year' => $targetYear,
                'payment_method' => '',
                'year_payment_method' => '',
            ],
            'options' => [
                'charity_types' => $charityTypes,
                'payment_methods' => CharityTransaction::paymentMethodOptions(),
            ],
            'routes' => [
                'summary' => $summaryRoute,
            ],
        ], $this->summaryPayload($defaultTypeId, $defaultTypeId, $targetYear));
    }

    public function summaryCards(?int $typeId = null, ?string $paymentMethod = null): array
    {
        $context = $this->partnerContext();
        $today = now()->toDateString();
        $year = (int) now()->year;
        $currency = $this->currencyCode();

        $todayQuery = $this->transactionBaseQuery($context, $typeId, $paymentMethod)
            ->paid()
            ->createdOn($today);

        $yearQuery = $this->transactionBaseQuery($context, $typeId, $paymentMethod)
            ->paid()
            ->createdInYear($year);

        $todayTotals = $this->receiptTotalsService->totalsForQuery($todayQuery);
        $yearTotals = $this->receiptTotalsService->totalsForQuery($yearQuery);

        return [
            'today' => [
                'total_money' => $todayTotals['total_money'],
                'total_money_label' => $this->formatMoney($todayTotals['total_money'], $currency),
                'total_rice' => $todayTotals['total_rice'],
                'total_rice_label' => $this->formatDecimal($todayTotals['total_rice']),
                'total_transactions' => $todayTotals['count'],
            ],
            'year' => [
                'total_money' => $yearTotals['total_money'],
                'total_money_label' => $this->formatMoney($yearTotals['total_money'], $currency),
                'total_rice' => $yearTotals['total_rice'],
                'total_rice_label' => $this->formatDecimal($yearTotals['total_rice']),
                'total_transactions' => $yearTotals['count'],
            ],
        ];
    }

    public function yearSummary(?int $typeId = null, ?int $year = null, ?string $paymentMethod = null): array
    {
        $context = $this->partnerContext();
        $targetYear = (int) ($year ?: now()->year);
        $currency = $this->currencyCode();

        $query = $this->transactionBaseQuery($context, $typeId, $paymentMethod)
            ->paid()
            ->createdInYear($targetYear);

        $totals = $this->receiptTotalsService->totalsForQuery($query);

        return [
            'year' => $targetYear,
            'total_money' => $totals['total_money'],
            'total_money_label' => $this->formatMoney($totals['total_money'], $currency),
            'total_rice' => $totals['total_rice'],
            'total_rice_label' => $this->formatDecimal($totals['total_rice']),
            'total_transactions' => $totals['count'],
        ];
    }

    public function dailyRecapData(?string $date): array
    {
        $recapDate = now()->startOfDay();
        if (! empty($date)) {
            try {
                $recapDate = Carbon::parse($date)->startOfDay();
            } catch (\Throwable $exception) {
                $recapDate = now()->startOfDay();
            }
        }

        $context = $this->partnerContext();

        $rows = $this->transactionBaseQuery($context)
            ->withCharityRelations()
            ->createdOn($recapDate->toDateString())
            ->get()
            ->groupBy(fn (CharityTransaction $transaction) => $transaction->charityType?->source?->name ?? __('messages.unknown'))
            ->map(function ($items, $label) {
                $totalMoney = (float) $items->sum(fn (CharityTransaction $item) => $item->detailMoneyAmount());
                $totalRice = (float) $items->sum(fn (CharityTransaction $item) => $item->detailRiceAmount());

                return [
                    'label' => $label,
                    'total_money' => $totalMoney,
                    'total_money_label' => $this->formatMoney($totalMoney),
                    'total_rice' => $totalRice,
                    'count' => (int) $items->count(),
                ];
            })
            ->values();

        $currency = $this->currencyCode();

        return [
            'rows' => $rows,
            'recapDate' => $recapDate,
            'organizationName' => $context['organization_name'] ?? null,
            'totalMoney' => (float) $rows->sum('total_money'),
            'totalMoneyLabel' => $this->formatMoney((float) $rows->sum('total_money'), $currency),
            'totalRice' => (float) $rows->sum('total_rice'),
            'totalCount' => (int) $rows->sum('count'),
            'currencyCode' => $currency,
        ];
    }

    public function formPayload(CharityTransaction $transaction, bool $modal = false): array
    {
        $context = $this->partnerContext();
        $nowYear = (int) now()->year;

        $charityTypeQuery = CharityType::query()
            ->with('source')
            ->where('year', $nowYear)
            ->orderBy('id', 'desc');

        if (! empty($context['organization_id'])) {
            $charityTypeQuery->where('organization_id', $context['organization_id']);
        }

        $paymentQuery = CharityPayment::query()
            ->where('is_active', true)
            ->whereIn('type', ['transfer', 'qris'])
            ->with('bank')
            ->orderBy('id', 'desc');

        if (! empty($context['organization_id'])) {
            $paymentQuery->where('organization_id', $context['organization_id']);
        }

        $payments = $paymentQuery->get()->map(function (CharityPayment $payment) {
            $bankLabel = $payment->bank?->name;

            return [
                'id' => $payment->id,
                'type' => $payment->type,
                'bank_name' => $bankLabel,
                'account_name' => $payment->account_name,
                'account_number' => $payment->account_number,
                'label' => trim(implode(' - ', array_filter([
                    $bankLabel,
                    $payment->account_name,
                    $payment->account_number,
                ]))),
            ];
        })->values();

        $charityTypes = $charityTypeQuery->get()->map(function (CharityType $charityType) {
            return [
                'id' => $charityType->id,
                'name' => $charityType->source?->name,
                'source_id' => $charityType->source_id,
                'min_amount' => $charityType->min_amount,
                'max_amount' => $charityType->max_amount,
                'is_rice' => (bool) $charityType->is_rice,
                'total_rice' => $charityType->total_rice,
                'package_amount' => $charityType->package_amount,
                'year' => $charityType->year,
                'slug' => $charityType->source?->slug,
            ];
        })->values();

        $defaultCharityTypeId = $charityTypes->first()['id'] ?? null;

        $detailPayload = $this->detailPayloadForForm($transaction);
        $detailPayload['is_rice'] = (bool) ($detailPayload['is_rice'] ?? false);

        $detailMoney = (float) ($detailPayload['amount_money'] ?? 0);
        $detailRice = (float) ($detailPayload['amount_rice'] ?? 0);

        $payload = [
            'mode' => $transaction->exists ? 'edit' : 'create',
            'context' => $context,
            'ui' => [
                'modal' => $modal,
            ],
            'routes' => [
                'store' => route('mosque.charity-transactions.store'),
                'update' => $transaction->exists ? route('mosque.charity-transactions.update', $transaction) : null,
            ],
            'form' => [
                'id' => $transaction->id,
                'organization_id' => $transaction->organization_id ?? $context['organization_id'] ?? null,
                'charity_type_id' => $transaction->charity_type_id ?? $defaultCharityTypeId,
                'year' => $transaction->year ?? $nowYear,
                'payer_name' => $transaction->payer_name,
                'payer_phone' => $transaction->payer_phone,
                'payer_email' => $transaction->payer_email,
                'payment_method' => $transaction->payment_method ?? 'cash',
                'charity_payment_id' => $transaction->charity_payment_id,
                'is_package' => (bool) $transaction->is_package,
                'use_same_package_amount' => (bool) $transaction->use_same_package_amount,
                'is_input_family_members' => (bool) $transaction->is_input_family_members,
                'representative_total_money' => $transaction->representative_total_money,
                'package_amount_each' => $transaction->package_amount_each,
                'package_members_count' => $transaction->package_members_count,
                'package_payers' => $transaction->packagePayers->map(function (CharityTransactionPayer $payer) {
                    return [
                        'payer_name' => $payer->payer_name,
                        'payer_phone' => $payer->payer_phone,
                        'payer_email' => $payer->payer_email,
                        'total_money' => $payer->total_money,
                        'notes' => $payer->notes,
                    ];
                })->values()->toArray(),
                'status' => $transaction->status ?? 'paid',
                'total_money' => $detailMoney > 0 ? $detailMoney : $transaction->total_money,
                'total_rice' => $detailRice > 0 ? $detailRice : $transaction->total_rice,
                'notes' => $transaction->notes,
                'detail' => $detailPayload,
            ],
            'options' => [
                'charity_types' => $charityTypes,
                'payment_methods' => CharityTransaction::paymentMethodOptions(),
                'payments' => $payments,
                'statuses' => [
                    ['value' => 'draft', 'label' => __('messages.draft')],
                    ['value' => 'paid', 'label' => __('messages.paid')],
                    ['value' => 'cancelled', 'label' => __('messages.cancelled')],
                ],
            ],
        ];

        return $payload;
    }

    public function charityTypeOptions(?array $context = null, ?int $year = null): array
    {
        $targetYear = (int) ($year ?: now()->year);
        $query = CharityType::query()
            ->with('source')
            ->where('year', $targetYear)
            ->orderBy('id', 'desc');

        if (! empty($context['organization_id'])) {
            $query->where('organization_id', $context['organization_id']);
        }

        return $query->get()
            ->map(fn (CharityType $type) => [
                'id' => $type->id,
                'name' => $type->source?->name ?? '-',
                'slug' => $type->source?->slug,
                'year' => $type->year,
            ])
            ->values()
            ->toArray();
    }

    protected function syncDetail(CharityTransaction $transaction, array $detail, bool $replace = false): void
    {
        $detail = array_filter($detail, fn ($value) => $value !== null);
        $detail['charity_transaction_id'] = $transaction->id;

        $receiptClass = match ($detail['type']) {
            'zakat-fitrah' => CharityFitrahReceipt::class,
            'fidyah' => CharityFidyaReceipt::class,
            'zakat-mal' => CharityMalReceipt::class,
            'infaq' => CharityDonationReceipt::class,
            'sedekah' => CharityAlmsReceipt::class,
            'waqf' => CharityEndowmentReceipt::class,
            default => null,
        };

        if (! $receiptClass) {
            return;
        }

        if ($replace) {
            $receiptClass::query()->where('charity_transaction_id', $transaction->id)->delete();
        }

        $receiptClass::create($detail);
    }

    protected function syncPackagePayers(CharityTransaction $transaction, array $packagePayers): void
    {
        $transaction->packagePayers()->delete();

        if (empty($packagePayers)) {
            return;
        }

        foreach ($packagePayers as $payer) {
            CharityTransactionPayer::create([
                'charity_transaction_id' => $transaction->id,
                'payer_name' => $payer['payer_name'],
                'payer_phone' => $payer['payer_phone'] ?? null,
                'payer_email' => $payer['payer_email'] ?? null,
                'total_money' => $payer['total_money'] ?? 0,
                'notes' => $payer['notes'] ?? null,
            ]);
        }
    }

    protected function formatMoney(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: $this->currencyCode());

        try {
            return Money::{$currency}($amount)->format(App::currentLocale());
        } catch (\Throwable $exception) {
            return Money::IDR($amount)->format(App::currentLocale());
        }
    }

    protected function currencyCode(): string
    {
        return strtoupper((string) config('money.defaultCurrency', 'IDR'));
    }

    protected function formatDecimal(float $value): string
    {
        return Number::format($value, 2, 2, App::currentLocale());
    }

    protected function transactionBaseQuery(
        ?array $context = null,
        ?int $charityTypeId = null,
        ?string $paymentMethod = null
    ): Builder
    {
        return CharityTransaction::query()
            ->forOrganization($context['organization_id'] ?? null)
            ->forCharityType($charityTypeId)
            ->forPaymentMethod($paymentMethod);
    }

    protected function prepareTransactionPayload(array $data, ?CharityTransaction $current = null): array
    {
        $detailInput = $data['detail'] ?? [];
        $packagePayers = $data['package_payers'] ?? [];
        $inputTotalMoney = isset($data['total_money']) ? (float) $data['total_money'] : null;
        $inputTotalRice = isset($data['total_rice']) ? (float) $data['total_rice'] : null;
        $inputNotes = $data['notes'] ?? null;
        $isInputFamilyMembers = (bool) ($data['is_input_family_members'] ?? false);
        $representativeTotalMoney = isset($data['representative_total_money']) ? (float) $data['representative_total_money'] : null;

        unset(
            $data['detail'],
            $data['package_payers'],
            $data['is_input_family_members'],
            $data['representative_total_money'],
            $data['total_money'],
            $data['total_rice'],
            $data['notes'],
            $data['received_at']
        );

        $data['year'] = (int) now()->year;
        $data['status'] = $data['status'] ?? $current?->status ?? 'paid';
        $data['received_by'] = $data['received_by'] ?? $current?->received_by ?? optional(auth()->user())->id;
        $data['is_package'] = (bool) ($data['is_package'] ?? false);
        $data['use_same_package_amount'] = (bool) ($data['use_same_package_amount'] ?? false);
        $data['is_input_family_members'] = (bool) $isInputFamilyMembers;
        $data['representative_total_money'] = ($data['is_package'] && ! $data['use_same_package_amount'])
            ? $representativeTotalMoney
            : null;

        if (empty($data['payer_name'])) {
            throw ValidationException::withMessages([
                'payer_name' => __('validation.required', ['attribute' => __('messages.payer_name')]),
            ]);
        }

        if (($data['payment_method'] ?? 'cash') === 'cash') {
            $data['charity_payment_id'] = null;
        }

        $charityType = CharityType::query()
            ->with('source')
            ->find($data['charity_type_id'] ?? null);

        if (! $charityType) {
            throw ValidationException::withMessages([
                'charity_type_id' => __('validation.exists', ['attribute' => __('messages.charity_type')]),
            ]);
        }

        $detail = $this->normalizedDetailFromInput(
            $charityType,
            $detailInput,
            $inputTotalMoney,
            $inputTotalRice,
            $inputNotes,
            $isInputFamilyMembers,
            $representativeTotalMoney
        );

        if ($data['is_package'] && $data['use_same_package_amount']) {
            if (! empty($data['package_amount_each'])) {
                $this->assertAmountWithinCharityRule($charityType, (float) $data['package_amount_each'], 'package_amount_each');
            }
        }

        if ($data['is_package'] && ! $data['use_same_package_amount']) {
            if ($representativeTotalMoney !== null) {
                $this->assertAmountWithinCharityRule($charityType, (float) $representativeTotalMoney, 'representative_total_money');
            }
        }

        $this->validatePackagePayers(
            $packagePayers,
            ! $data['use_same_package_amount'],
            $charityType,
            $isInputFamilyMembers
        );

        return [$data, $detail, $packagePayers];
    }

    protected function normalizedDetailFromInput(
        CharityType $charityType,
        array $detailInput,
        ?float $inputTotalMoney,
        ?float $inputTotalRice,
        ?string $inputNotes,
        bool $isInputFamilyMembers,
        ?float $representativeTotalMoney
    ): array {
        $normalized = [
            'type' => $charityType->source?->slug,
            'amount_money' => $inputTotalMoney,
            'amount_rice' => $inputTotalRice,
            'notes' => $inputNotes,
            'is_input_family_members' => $isInputFamilyMembers,
            'representative_total_money' => $representativeTotalMoney,
        ];

        if ($detailInput) {
            $normalized = array_merge($normalized, $detailInput);
        }

        $normalized['amount_money'] = isset($normalized['amount_money']) ? (float) $normalized['amount_money'] : null;
        $normalized['amount_rice'] = isset($normalized['amount_rice']) ? (float) $normalized['amount_rice'] : null;
        $normalized['is_rice'] = (bool) ($normalized['is_rice'] ?? false);

        if (! $normalized['is_rice']) {
            $normalized['amount_rice'] = null;
        }

        if ($normalized['is_rice'] && ($normalized['amount_rice'] === null || $normalized['amount_rice'] <= 0)) {
            throw ValidationException::withMessages([
                'total_rice' => __('validation.required', ['attribute' => __('messages.total_rice')]),
            ]);
        }

        if ($normalized['amount_money'] !== null) {
            $this->assertAmountWithinCharityRule($charityType, $normalized['amount_money'], 'total_money');
        }

        if (! $this->isRiceCapableType($charityType->source?->slug)) {
            $normalized['amount_rice'] = null;
        }

        return $normalized;
    }

    protected function assertAmountWithinCharityRule(CharityType $charityType, ?float $amount, string $field): void
    {
        if ($amount === null) {
            return;
        }

        if ($charityType->min_amount !== null && $amount < (float) $charityType->min_amount) {
            throw ValidationException::withMessages([
                $field => __('messages.amount_minimum_value', [
                    'amount' => $this->formatMoney((float) $charityType->min_amount),
                ]),
            ]);
        }

        if ($charityType->max_amount !== null && $amount > (float) $charityType->max_amount) {
            throw ValidationException::withMessages([
                $field => __('messages.amount_maximum_value', [
                    'amount' => $this->formatMoney((float) $charityType->max_amount),
                ]),
            ]);
        }
    }

    protected function validatePackagePayers(
        array $packagePayers,
        bool $requireAmount,
        ?CharityType $charityType = null,
        bool $requireName = false
    ): void
    {
        if (empty($packagePayers)) {
            return;
        }

        foreach ($packagePayers as $payer) {
            if ($requireName && (! isset($payer['payer_name']) || $payer['payer_name'] === '')) {
                throw ValidationException::withMessages([
                    'package_payers' => __('validation.required', ['attribute' => __('messages.payer_name')]),
                ]);
            }

            if (! isset($payer['payer_name']) || $payer['payer_name'] === '') {
                continue;
            }

            if ($requireAmount && (! isset($payer['total_money']) || $payer['total_money'] === '')) {
                throw ValidationException::withMessages([
                    'package_payers' => __('messages.package_payers_invalid_amount'),
                ]);
            }

            if ($requireAmount && $charityType) {
                $this->assertAmountWithinCharityRule($charityType, (float) $payer['total_money'], 'package_payers');
            }
        }
    }

    protected function detailPayloadForForm(CharityTransaction $transaction): array
    {
        if (! $transaction->exists) {
            return [
                'is_rice' => false,
                'amount_money' => null,
                'amount_rice' => null,
            ];
        }

        $detail = [
            'type' => $transaction->charityType?->source?->slug,
        ];

        if ($transaction->fitrahReceipt) {
            $detail = array_merge($detail, $transaction->fitrahReceipt->toArray());
        } elseif ($transaction->fidyaReceipt) {
            $detail = array_merge($detail, $transaction->fidyaReceipt->toArray());
        } elseif ($transaction->malReceipt) {
            $detail = array_merge($detail, $transaction->malReceipt->toArray());
        } elseif ($transaction->donationReceipt) {
            $detail = array_merge($detail, $transaction->donationReceipt->toArray());
        } elseif ($transaction->almsReceipt) {
            $detail = array_merge($detail, $transaction->almsReceipt->toArray());
        } elseif ($transaction->endowmentReceipt) {
            $detail = array_merge($detail, $transaction->endowmentReceipt->toArray());
        }

        return $detail;
    }

    protected function isRiceCapableType(?string $slug): bool
    {
        return in_array($slug, ['zakat-fitrah', 'fidyah'], true);
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
}
