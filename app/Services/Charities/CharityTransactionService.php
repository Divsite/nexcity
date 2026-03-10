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
use App\Services\Notifications\OpenclawWebhookService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            $this->notifyTransferIfNeeded($transaction);

            return $transaction;
        });
    }

    public function update(CharityTransaction $charityTransaction, array $data): CharityTransaction
    {
        return DB::transaction(function () use ($charityTransaction, $data) {
            $previousStatus = $charityTransaction->status;
            [$payload, $detail, $packagePayers] = $this->prepareTransactionPayload($data, $charityTransaction);
            $charityTransaction->update($payload);
            $this->syncDetail($charityTransaction, $detail, true);
            $this->syncPackagePayers($charityTransaction, $packagePayers);

            if ($previousStatus !== 'paid' && $charityTransaction->status === 'paid') {
                $this->notifyTransferIfNeeded($charityTransaction);
            }

            return $charityTransaction;
        });
    }

    protected function notifyTransferIfNeeded(CharityTransaction $transaction): void
    {
        if (! in_array($transaction->payment_method, ['transfer', 'qris'], true)) {
            return;
        }

        if ($transaction->status !== 'paid') {
            return;
        }

        app(OpenclawWebhookService::class)->sendTransferNotification($transaction);
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

    public function dailyRecapData(?string $date, ?int $organizationId = null): array
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
        $organizationId = $organizationId ?: ($context['organization_id'] ?? null);

        $transactions = $this->transactionBaseQuery(['organization_id' => $organizationId])
            ->paid()
            ->withCharityRelations()
            ->createdOn($recapDate->toDateString())
            ->get();

        $rows = $transactions
            ->groupBy(fn (CharityTransaction $transaction) => $transaction->charity_type_id ?: 0)
            ->map(function ($items) {
                $first = $items->first();
                $charityType = $first?->charityType;
                $sourceName = $charityType?->source?->name ?? __('messages.unknown');
                $year = $charityType?->year;
                $label = trim($sourceName . ($year ? ' - ' . $year : ''));

                $totalMoney = (float) $items->sum(fn (CharityTransaction $item) => $item->detailMoneyAmount());
                $totalRice = (float) $items->sum(fn (CharityTransaction $item) => $item->detailRiceAmount());

                return [
                    'charity_type_id' => $charityType?->id,
                    'charity_type_source_id' => $charityType?->source_id,
                    'name' => $sourceName,
                    'year' => $year,
                    'label' => $label,
                    'total_money' => $totalMoney,
                    'total_money_label' => $this->formatMoney($totalMoney),
                    'total_rice' => $totalRice,
                    'total_rice_label' => $this->formatDecimal($totalRice),
                    'count' => (int) $items->count(),
                ];
            })
            ->sortBy('label')
            ->values();

        $currency = $this->currencyCode();
        $paymentMethodLabels = CharityTransaction::paymentMethodLabels();
        $paymentMethodTotals = collect([
            CharityTransaction::PAYMENT_METHOD_CASH,
            CharityTransaction::PAYMENT_METHOD_TRANSFER,
            CharityTransaction::PAYMENT_METHOD_QRIS,
        ])->map(function (string $method) use ($transactions, $paymentMethodLabels, $currency) {
            $totalMoney = (float) $transactions
                ->where('payment_method', $method)
                ->sum(fn (CharityTransaction $transaction) => $transaction->detailMoneyAmount());

            return [
                'method' => $method,
                'label' => $paymentMethodLabels[$method] ?? $method,
                'total_money' => $totalMoney,
                'total_money_label' => $this->formatMoney($totalMoney, $currency),
            ];
        })->values();

        return [
            'rows' => $rows,
            'recapDate' => $recapDate,
            'organizationName' => $context['organization_name'] ?? null,
            'totalMoney' => (float) $rows->sum('total_money'),
            'totalMoneyLabel' => $this->formatMoney((float) $rows->sum('total_money'), $currency),
            'totalRice' => (float) $rows->sum('total_rice'),
            'totalCount' => (int) $rows->sum('count'),
            'paymentMethodTotals' => $paymentMethodTotals,
            'currencyCode' => $currency,
        ];
    }

    public function dailySummaryByOrganization(int $organizationId, ?string $date = null): array
    {
        $summary = $this->dailyRecapData($date, $organizationId);

        return [
            'date' => $summary['recapDate']->toDateString(),
            'total_money' => $summary['totalMoney'],
            'total_money_label' => $summary['totalMoneyLabel'],
            'total_rice' => $summary['totalRice'],
            'total_rice_label' => $this->formatDecimal((float) $summary['totalRice']),
            'total_transactions' => $summary['totalCount'],
            'payment_method_totals' => $summary['paymentMethodTotals'],
            'rows' => $summary['rows'],
            'currency' => $summary['currencyCode'],
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
            $qrisImage = $payment->qris_image_path
                ? asset('uploads/' . ltrim($payment->qris_image_path, '/'))
                : null;

            return [
                'id' => $payment->id,
                'type' => $payment->type,
                'bank_name' => $bankLabel,
                'account_name' => $payment->account_name,
                'account_number' => $payment->account_number,
                'qris_image_url' => $qrisImage,
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
                'use_multipliers' => (bool) $charityType->use_multipliers,
            ];
        })->values();

        $defaultCharityTypeId = $charityTypes->first()['id'] ?? null;

        $detailPayload = $this->detailPayloadForForm($transaction);
        $detailPayload['is_rice'] = (bool) ($detailPayload['is_rice'] ?? false);

        $multiplierCount = (int) ($transaction->multiplier_count ?: 1);
        $useMultipliers = (bool) ($transaction->charityType?->use_multipliers);
        $multiplierCount = $useMultipliers && $multiplierCount > 0 ? $multiplierCount : 1;

        $detailMoney = (float) ($detailPayload['amount_money'] ?? 0);
        $detailRice = (float) ($detailPayload['amount_rice'] ?? 0);

        if ($useMultipliers && $multiplierCount > 1 && $detailMoney > 0 && ! $transaction->is_package) {
            $detailMoney = $detailMoney / $multiplierCount;
            $detailPayload['amount_money'] = $detailMoney;
        }
        if ($useMultipliers && $multiplierCount > 1 && $detailRice > 0 && ! $transaction->is_package) {
            $detailRice = $detailRice / $multiplierCount;
            $detailPayload['amount_rice'] = $detailRice;
        }

        $detailPayload['is_money'] = $detailMoney > 0
            ? true
            : (! $detailPayload['is_rice']);

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
                'representative_total_money' => ($useMultipliers && $multiplierCount > 1 && $transaction->representative_total_money)
                    ? ((float) $transaction->representative_total_money / $multiplierCount)
                    : $transaction->representative_total_money,
                'representative_total_rice' => $transaction->is_package
                    ? $this->packageRepresentativeRice($transaction, $useMultipliers ? $multiplierCount : 1)
                    : null,
                'package_amount_each' => $transaction->package_amount_each,
                'package_members_count' => $transaction->package_members_count,
                'package_payers' => $transaction->packagePayers->map(function (CharityTransactionPayer $payer) use ($useMultipliers) {
                    $money = $payer->total_money;
                    $rice = $payer->total_rice;
                    $payerMultiplier = (int) ($payer->multiplier_count ?: 1);
                    if ($useMultipliers && $payerMultiplier > 1 && $money !== null) {
                        $money = (float) $money / $payerMultiplier;
                    }
                    if ($useMultipliers && $payerMultiplier > 1 && $rice !== null) {
                        $rice = (float) $rice / $payerMultiplier;
                    }
                    return [
                        'payer_name' => $payer->payer_name,
                        'payer_phone' => $payer->payer_phone,
                        'payer_email' => $payer->payer_email,
                        'is_money' => (bool) $payer->is_money,
                        'is_rice' => (bool) $payer->is_rice,
                        'multiplier_count' => $payer->multiplier_count,
                        'total_money' => $money,
                        'total_rice' => $rice,
                        'notes' => $payer->notes,
                    ];
                })->values()->toArray(),
                'status' => $transaction->status ?? 'paid',
                'total_money' => $detailMoney > 0
                    ? $detailMoney
                    : (($useMultipliers && $multiplierCount > 1 && ! $transaction->is_package && $transaction->total_money)
                        ? ((float) $transaction->total_money / $multiplierCount)
                        : $transaction->total_money),
                'total_rice' => $detailRice > 0
                    ? $detailRice
                    : (($useMultipliers && $multiplierCount > 1 && ! $transaction->is_package && $transaction->total_rice)
                        ? ((float) $transaction->total_rice / $multiplierCount)
                        : $transaction->total_rice),
                'amount_money' => ! $transaction->is_package
                    ? (($useMultipliers && $multiplierCount > 1 && $detailMoney > 0)
                        ? ((float) $detailMoney / $multiplierCount)
                        : $detailMoney)
                    : null,
                'amount_rice' => ! $transaction->is_package
                    ? (($useMultipliers && $multiplierCount > 1 && $detailRice > 0)
                        ? ((float) $detailRice / $multiplierCount)
                        : $detailRice)
                    : null,
                'multiplier_count' => $useMultipliers ? $multiplierCount : null,
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
                'is_money' => array_key_exists('is_money', $payer) ? (bool) $payer['is_money'] : true,
                'is_rice' => array_key_exists('is_rice', $payer) ? (bool) $payer['is_rice'] : false,
                'multiplier_count' => $payer['multiplier_count'] ?? null,
                'total_money' => $payer['total_money'] ?? 0,
                'total_rice' => $payer['total_rice'] ?? 0,
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
        $inputAmountMoney = isset($data['amount_money']) ? (float) $data['amount_money'] : null;
        $inputAmountRice = isset($data['amount_rice']) ? (float) $data['amount_rice'] : null;
        $inputTotalMoney = isset($data['total_money']) ? (float) $data['total_money'] : null;
        $inputTotalRice = isset($data['total_rice']) ? (float) $data['total_rice'] : null;
        $inputNotes = $data['notes'] ?? null;
        $isInputFamilyMembers = (bool) ($data['is_input_family_members'] ?? false);
        $representativeTotalMoney = isset($data['representative_total_money']) ? (float) $data['representative_total_money'] : null;
        $representativeTotalRice = isset($data['representative_total_rice']) ? (float) $data['representative_total_rice'] : null;
        $repPayMoney = array_key_exists('is_money', $detailInput) ? (bool) $detailInput['is_money'] : true;
        $repPayRice = array_key_exists('is_rice', $detailInput) ? (bool) $detailInput['is_rice'] : false;
        $packageHasMoney = collect($packagePayers)->contains(fn ($payer) => ! empty($payer['is_money']));
        $packageHasRice = collect($packagePayers)->contains(fn ($payer) => ! empty($payer['is_rice']));
        $payMoney = $repPayMoney || $packageHasMoney;
        $payRice = $repPayRice || $packageHasRice;

        unset(
            $data['detail'],
            $data['package_payers'],
            $data['is_input_family_members'],
            $data['representative_total_money'],
            $data['representative_total_rice'],
            $data['amount_money'],
            $data['amount_rice'],
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

        if (! $repPayMoney) {
            $representativeTotalMoney = null;
            $data['representative_total_money'] = null;
        }

        if (! $payMoney) {
            $inputTotalMoney = null;
            $representativeTotalMoney = null;
            $data['package_amount_each'] = null;
            $data['representative_total_money'] = null;
            $data['total_money'] = null;
        }

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

        if (! $data['is_package']) {
            if ($inputAmountMoney === null) {
                $inputAmountMoney = $inputTotalMoney;
            }
            if ($inputAmountRice === null) {
                $inputAmountRice = $inputTotalRice;
            }
        }

        $multiplierCount = (int) ($data['multiplier_count'] ?? 1);
        if ($charityType->use_multipliers && ($payMoney || $payRice)) {
            if ($multiplierCount < 1) {
                throw ValidationException::withMessages([
                    'multiplier_count' => __('validation.required', ['attribute' => __('messages.total_days')]),
                ]);
            }
            $data['multiplier_count'] = $multiplierCount;
        } else {
            $multiplierCount = 1;
            $data['multiplier_count'] = null;
        }

        $detail = $this->normalizedDetailFromInput(
            $charityType,
            $detailInput,
            $inputAmountMoney,
            $inputAmountRice,
            $inputNotes,
            $isInputFamilyMembers,
            $representativeTotalMoney,
            $payMoney,
            $payRice,
            $data['is_package']
        );

        if ($payMoney && $data['is_package'] && $data['use_same_package_amount']) {
            if (! empty($data['package_amount_each'])) {
                $this->assertAmountWithinCharityRule($charityType, (float) $data['package_amount_each'], 'package_amount_each');
            }
        }

        if ($payMoney && $data['is_package'] && ! $data['use_same_package_amount']) {
            if ($representativeTotalMoney !== null) {
                $this->assertAmountWithinCharityRule($charityType, (float) $representativeTotalMoney, 'representative_total_money');
            }
        }

        $this->validatePackagePayers(
            $packagePayers,
            $charityType,
            $isInputFamilyMembers,
            $charityType->use_multipliers && ($payMoney || $payRice)
        );

        if ($payRice && $data['is_package']) {
            $requiresRepresentativeRice = ! $isInputFamilyMembers || $repPayRice;
            if ($requiresRepresentativeRice) {
                if ($representativeTotalRice === null || $representativeTotalRice <= 0) {
                    throw ValidationException::withMessages([
                        'representative_total_rice' => __('validation.required', ['attribute' => __('messages.total_rice')]),
                    ]);
                }

                if ($charityType->total_rice !== null && $representativeTotalRice < (float) $charityType->total_rice) {
                    throw ValidationException::withMessages([
                        'representative_total_rice' => __('messages.rice_minimum_value', [
                            'amount' => $this->formatDecimal((float) $charityType->total_rice),
                        ]),
                    ]);
                }
            }
        }

        if ($charityType->use_multipliers && ($payMoney || $payRice)) {
            if (! $data['is_package']) {
                if ($inputAmountMoney !== null) {
                    $inputAmountMoney = $inputAmountMoney * $multiplierCount;
                }
                if ($inputAmountRice !== null) {
                    $inputAmountRice = $inputAmountRice * $multiplierCount;
                }
            }

            if ($representativeTotalMoney !== null) {
                $representativeTotalMoney = $representativeTotalMoney * $multiplierCount;
            }
            if ($representativeTotalRice !== null) {
                $representativeTotalRice = $representativeTotalRice * $multiplierCount;
            }

            $packagePayers = collect($packagePayers)->map(function ($payer) {
                $multiplier = (int) ($payer['multiplier_count'] ?? 1);
                $multiplier = $multiplier > 0 ? $multiplier : 1;
                $payer['multiplier_count'] = $multiplier;

                if (! empty($payer['is_money']) && isset($payer['total_money'])) {
                    $payer['total_money'] = (float) $payer['total_money'] * $multiplier;
                }
                if (! empty($payer['is_rice']) && isset($payer['total_rice'])) {
                    $payer['total_rice'] = (float) $payer['total_rice'] * $multiplier;
                }

                if (empty($payer['is_money']) && empty($payer['is_rice'])) {
                    $payer['multiplier_count'] = null;
                }

                return $payer;
            })->values()->toArray();
        }

        if ($data['is_package'] && ! $data['use_same_package_amount']) {
            $data['representative_total_money'] = $representativeTotalMoney;
        }

        $totalMoney = null;
        $totalRice = null;

        if ($payMoney) {
            if ($data['is_package']) {
                if ($data['use_same_package_amount']) {
                    $baseEach = (float) ($data['package_amount_each'] ?? 0);
                    if ($isInputFamilyMembers) {
                        $repTotal = $baseEach * $multiplierCount;
                        $payersTotal = collect($packagePayers)->sum(fn ($payer) => (float) ($payer['total_money'] ?? 0));
                        $totalMoney = $repTotal + $payersTotal;
                    } else {
                        $membersCount = (int) ($data['package_members_count'] ?? 0);
                        $totalMoney = $baseEach * $multiplierCount * max($membersCount, 0);
                    }
                } else {
                    $repTotal = (float) ($representativeTotalMoney ?? 0);
                    $payersTotal = collect($packagePayers)->sum(fn ($payer) => (float) ($payer['total_money'] ?? 0));
                    $totalMoney = $repTotal + $payersTotal;
                }
            } else {
                $totalMoney = (float) ($inputAmountMoney ?? 0);
            }
        }

        if ($payRice) {
            if ($data['is_package']) {
                if ($isInputFamilyMembers) {
                    $repRiceTotal = (float) ($representativeTotalRice ?? 0);
                    $payersRiceTotal = collect($packagePayers)->sum(fn ($payer) => (float) ($payer['total_rice'] ?? 0));
                    $totalRice = $repRiceTotal + $payersRiceTotal;
                } else {
                    $membersCount = (int) ($data['package_members_count'] ?? 0);
                    $repRice = (float) ($representativeTotalRice ?? 0);
                    $totalRice = $repRice * max($membersCount, 0);
                }
            } else {
                $totalRice = (float) ($inputAmountRice ?? 0);
            }
        }

        $data['total_money'] = $payMoney ? $totalMoney : null;
        $data['total_rice'] = $payRice ? $totalRice : null;

        $detail['amount_money'] = $payMoney ? $totalMoney : null;
        $detail['amount_rice'] = $payRice ? $totalRice : null;

        return [$data, $detail, $packagePayers];
    }

    protected function normalizedDetailFromInput(
        CharityType $charityType,
        array $detailInput,
        ?float $inputTotalMoney,
        ?float $inputTotalRice,
        ?string $inputNotes,
        bool $isInputFamilyMembers,
        ?float $representativeTotalMoney,
        bool $payMoney,
        bool $payRice,
        bool $isPackage
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
        $normalized['is_rice'] = $payRice;

        unset($normalized['is_money']);

        if (! $payMoney && ! $payRice) {
            throw ValidationException::withMessages([
                'payment_option' => __('messages.payment_option_required'),
            ]);
        }

        if (! $payMoney) {
            $normalized['amount_money'] = null;
        }

        if (! $normalized['is_rice']) {
            $normalized['amount_rice'] = null;
        }

        if (! $isPackage) {
            if ($normalized['is_rice'] && ($normalized['amount_rice'] === null || $normalized['amount_rice'] <= 0)) {
                throw ValidationException::withMessages([
                    'amount_rice' => __('validation.required', ['attribute' => __('messages.total_rice')]),
                ]);
            }

            if ($normalized['is_rice'] && $charityType->total_rice !== null) {
                if ($normalized['amount_rice'] !== null && $normalized['amount_rice'] < (float) $charityType->total_rice) {
                    throw ValidationException::withMessages([
                        'amount_rice' => __('messages.rice_minimum_value', [
                            'amount' => $this->formatDecimal((float) $charityType->total_rice),
                        ]),
                    ]);
                }
            }

            if ($payMoney && ($normalized['amount_money'] === null || $normalized['amount_money'] <= 0)) {
                throw ValidationException::withMessages([
                    'amount_money' => __('validation.required', ['attribute' => __('messages.amount')]),
                ]);
            }

            if ($normalized['amount_money'] !== null) {
                $this->assertAmountWithinCharityRule($charityType, $normalized['amount_money'], 'amount_money');
            }
        }

        if (! $this->isRiceCapableType($charityType->source?->slug)) {
            $normalized['amount_rice'] = null;
            $normalized['is_rice'] = false;
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
        ?CharityType $charityType = null,
        bool $requireName = false,
        bool $useMultipliers = false
    ): void
    {
        if (empty($packagePayers)) {
            return;
        }

        foreach ($packagePayers as $index => $payer) {
            if ($requireName && (! isset($payer['payer_name']) || $payer['payer_name'] === '')) {
                throw ValidationException::withMessages([
                    'package_payers.' . $index . '.payer_name' => __('validation.required', ['attribute' => __('messages.payer_name')]),
                ]);
            }

            if (! isset($payer['payer_name']) || $payer['payer_name'] === '') {
                continue;
            }

            $payMoney = array_key_exists('is_money', $payer) ? (bool) $payer['is_money'] : true;
            $payRice = array_key_exists('is_rice', $payer) ? (bool) $payer['is_rice'] : false;

            if (! $payMoney && ! $payRice) {
                throw ValidationException::withMessages([
                    'package_payers.' . $index . '.payer_name' => __('messages.payment_option_required'),
                ]);
            }

            if ($payMoney && (! isset($payer['total_money']) || $payer['total_money'] === '')) {
                throw ValidationException::withMessages([
                    'package_payers.' . $index . '.total_money' => __('messages.package_payers_invalid_amount'),
                ]);
            }

            if ($useMultipliers && ($payMoney || $payRice)) {
                $multiplier = (int) ($payer['multiplier_count'] ?? 0);
                if ($multiplier < 1) {
                    throw ValidationException::withMessages([
                        'package_payers.' . $index . '.multiplier_count' => __('validation.required', ['attribute' => __('messages.total_days')]),
                    ]);
                }
            }

            if ($payRice && (! isset($payer['total_rice']) || $payer['total_rice'] === '')) {
                throw ValidationException::withMessages([
                    'package_payers.' . $index . '.total_rice' => __('validation.required', ['attribute' => __('messages.total_rice')]),
                ]);
            }

            if ($payRice && isset($payer['total_rice']) && (float) $payer['total_rice'] <= 0) {
                throw ValidationException::withMessages([
                    'package_payers.' . $index . '.total_rice' => __('validation.gt.numeric', ['attribute' => __('messages.total_rice'), 'value' => 0]),
                ]);
            }

            if ($payMoney && $charityType) {
                $this->assertAmountWithinCharityRule($charityType, (float) $payer['total_money'], 'package_payers.' . $index . '.total_money');
            }

            if ($payRice && $charityType && $charityType->total_rice !== null) {
                if ((float) $payer['total_rice'] < (float) $charityType->total_rice) {
                    throw ValidationException::withMessages([
                        'package_payers.' . $index . '.total_rice' => __('messages.rice_minimum_value', [
                            'amount' => $this->formatDecimal((float) $charityType->total_rice),
                        ]),
                    ]);
                }
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

    protected function packageRepresentativeRice(CharityTransaction $transaction, int $multiplierCount = 1): ?float
    {
        $totalRice = (float) ($transaction->total_rice ?? 0);
        if ($totalRice <= 0) {
            return null;
        }

        $multiplierCount = $multiplierCount > 0 ? $multiplierCount : 1;

        if (! $transaction->is_input_family_members) {
            $membersCount = (int) ($transaction->package_members_count ?: 1);
            $divisor = max($membersCount * $multiplierCount, 1);
            return $totalRice / $divisor;
        }

        $payersRice = (float) $transaction->packagePayers->sum('total_rice');
        $representativeTotal = max($totalRice - $payersRice, 0);

        if ($multiplierCount > 1) {
            $representativeTotal = $representativeTotal / $multiplierCount;
        }

        return $representativeTotal;
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
}
