<?php

namespace App\Http\Controllers\Charities;

use Cknow\Money\Money;
use App\Http\Controllers\Controller;
use App\Http\Requests\Charities\StoreCharityTransactionRequest;
use App\Http\Requests\Charities\UpdateCharityTransactionRequest;
use App\Models\Charities\CharityAlmsReceipt;
use App\Models\Charities\CharityDonationReceipt;
use App\Models\Charities\CharityEndowmentReceipt;
use App\Models\Charities\CharityFidyaReceipt;
use App\Models\Charities\CharityFitrahReceipt;
use App\Models\Charities\CharityMalReceipt;
use App\Models\Charities\CharityTransactionPayer;
use App\Models\CharityPayments\CharityPayment;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CharityTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-mosque-charity-transactions')->only('index');
        $this->middleware('permission:add-mosque-charity-transactions')->only(['create', 'store']);
        $this->middleware('permission:edit-mosque-charity-transactions')->only(['edit', 'update']);
        $this->middleware('permission:delete-mosque-charity-transactions')->only('destroy');
        $this->middleware('permission:browse-mosque-charity-transactions')->only('dailyRecap');
    }

    public function index(): View
    {
        return view('charities.index', [
            'formPayload' => $this->formPayload(new CharityTransaction(), true),
            'summaryCards' => $this->summaryCards(),
            'dailyRecapPrintUrl' => route('mosque.charity-transactions.daily-recap.print'),
        ]);
    }

    public function create(): View
    {
        return view('charities.create', [
            'formPayload' => $this->formPayload(new CharityTransaction()),
        ]);
    }

    public function store(StoreCharityTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $context = $this->partnerContext();
        $isModal = $request->input('_mode') === 'modal';

        if ($context) {
            $data['organization_id'] = $context['organization_id'];
        }

        $transaction = DB::transaction(function () use ($data) {
            [$payload, $detail, $packagePayers] = $this->prepareTransactionPayload($data);
            $transaction = CharityTransaction::create($payload);
            $this->syncDetail($transaction, $detail);
            $this->syncPackagePayers($transaction, $packagePayers);

            activity(__('messages.charity_transactions'))
                ->causedBy(auth()->user())
                ->performedOn($transaction)
                ->log(__('messages.charity_transactions_has_been_created', ['name' => $transaction->payer_name ?? '#' . $transaction->id]));

            return $transaction;
        });

        if ($isModal) {
            return response()->json([
                'success' => true,
                'message' => __('messages.created_successfully'),
                'id' => $transaction->id,
            ]);
        }

        flash()->success(__('messages.created_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-transactions.index'),
        ]);
    }

    public function edit(CharityTransaction $charityTransaction): View
    {
        return view('charities.edit', [
            'formPayload' => $this->formPayload($charityTransaction),
        ]);
    }

    public function update(UpdateCharityTransactionRequest $request, CharityTransaction $charityTransaction): JsonResponse
    {
        $data = $request->validated();
        $context = $this->partnerContext();

        if ($context) {
            $data['organization_id'] = $context['organization_id'];
        }

        DB::transaction(function () use ($charityTransaction, $data) {
            [$payload, $detail, $packagePayers] = $this->prepareTransactionPayload($data, $charityTransaction);
            $charityTransaction->update($payload);
            $this->syncDetail($charityTransaction, $detail, true);
            $this->syncPackagePayers($charityTransaction, $packagePayers);

            activity(__('messages.charity_transactions'))
                ->causedBy(auth()->user())
                ->performedOn($charityTransaction)
                ->log(__('messages.charity_transactions_has_been_updated', ['name' => $charityTransaction->payer_name ?? '#' . $charityTransaction->id]));
        });

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-transactions.index'),
        ]);
    }

    public function dailyRecap(Request $request): View
    {
        $date = $request->query('date');
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
            ->with([
                'charityType.source',
                'fitrahReceipt',
                'fidyaReceipt',
                'malReceipt',
                'donationReceipt',
                'almsReceipt',
                'endowmentReceipt',
            ])
            ->whereDate('charity_transactions.created_at', $recapDate->toDateString())
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

        return view('charities.recap-daily-print', [
            'rows' => $rows,
            'recapDate' => $recapDate,
            'organizationName' => $context['organization_name'] ?? null,
            'totalMoney' => (float) $rows->sum('total_money'),
            'totalMoneyLabel' => $this->formatMoney((float) $rows->sum('total_money'), $currency),
            'totalRice' => (float) $rows->sum('total_rice'),
            'totalCount' => (int) $rows->sum('count'),
            'currencyCode' => $currency,
        ]);
    }

    protected function formPayload(CharityTransaction $transaction, bool $modal = false): array
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

        $packagePayers = [];
        $useSamePackageAmount = (bool) ($transaction->use_same_package_amount ?? false);
        $packageAmountEach = $transaction->package_amount_each !== null ? (float) $transaction->package_amount_each : null;
        $packageMembersCount = $transaction->package_members_count !== null ? (int) $transaction->package_members_count : null;
        $isInputFamilyMembers = false;
        $representativeTotalMoney = null;
        $detailForForm = [
            'is_rice' => false,
            'total_money' => null,
            'total_rice' => null,
            'notes' => null,
            'detail' => ['is_rice' => false],
        ];

        if ($transaction->exists) {
            $transaction->loadMissing([
                'fitrahReceipt',
                'fidyaReceipt',
                'malReceipt',
                'donationReceipt',
                'almsReceipt',
                'endowmentReceipt',
            ]);

            $detailForForm = $this->detailPayloadForForm($transaction);

            $packagePayers = $transaction->payers()
                ->get(['payer_name', 'payer_phone', 'payer_email', 'total_money', 'notes'])
                ->toArray();

            $amounts = collect($packagePayers)
                ->pluck('total_money')
                ->map(fn ($value) => $value !== null ? (float) $value : null)
                ->filter(fn ($value) => $value !== null)
                ->unique()
                ->values();

            if (! $useSamePackageAmount && $amounts->count() === 1) {
                $useSamePackageAmount = true;
                $packageAmountEach = (float) $amounts->first();
            }

            if ($packageMembersCount === null) {
                $packageMembersCount = count($packagePayers) > 0 ? count($packagePayers) : null;
            }

            $isInputFamilyMembers = (bool) $transaction->is_package && (! $useSamePackageAmount || count($packagePayers) > 0);
            if ((bool) $transaction->is_package && ! $useSamePackageAmount) {
                $familyMembersTotal = (float) collect($packagePayers)->sum(fn (array $payer) => (float) ($payer['total_money'] ?? 0));
                $representativeTotalMoney = max(0, (float) $detailForForm['total_money'] - $familyMembersTotal);
            }
        }

        return [
            'mode' => $transaction->exists ? 'edit' : 'create',
            'ui' => [
                'modal' => $modal,
            ],
            'context' => $context,
            'form' => [
                'id' => $transaction->id,
                'organization_id' => $transaction->organization_id,
                'charity_type_id' => $transaction->charity_type_id,
                'year' => $transaction->year ?? $nowYear,
                'payer_name' => $transaction->payer_name,
                'payer_phone' => $transaction->payer_phone,
                'payer_email' => $transaction->payer_email,
                'payment_method' => $transaction->payment_method ?? 'cash',
                'charity_payment_id' => $transaction->charity_payment_id,
                'is_package' => (bool) $transaction->is_package,
                'use_same_package_amount' => $useSamePackageAmount,
                'is_input_family_members' => $isInputFamilyMembers,
                'representative_total_money' => $representativeTotalMoney,
                'package_amount_each' => $packageAmountEach,
                'package_members_count' => $packageMembersCount,
                'package_payers' => $packagePayers,
                'status' => $transaction->status ?? 'paid',
                'total_money' => $detailForForm['total_money'],
                'total_rice' => $detailForForm['total_rice'],
                'notes' => $detailForForm['notes'],
                'detail' => $detailForForm['detail'],
            ],
            'options' => [
                'charity_types' => $charityTypeQuery->get(),
                'payment_methods' => [
                    ['value' => 'cash', 'label' => __('messages.cash')],
                    ['value' => 'transfer', 'label' => __('messages.transfer')],
                    ['value' => 'qris', 'label' => __('messages.qris')],
                ],
                'payments' => $paymentQuery->get(),
                'statuses' => [
                    ['value' => 'draft', 'label' => __('messages.draft')],
                    ['value' => 'paid', 'label' => __('messages.paid')],
                    ['value' => 'cancelled', 'label' => __('messages.cancelled')],
                ],
            ],
            'routes' => [
                'store' => route('mosque.charity-transactions.store'),
                'update' => $transaction->exists ? route('mosque.charity-transactions.update', $transaction) : null,
            ],
        ];
    }

    protected function syncDetail(CharityTransaction $transaction, array $detail, bool $replace = false): void
    {
        $type = $transaction->charityType?->source?->slug;

        if (! $type) {
            return;
        }

        if ($replace) {
            CharityFitrahReceipt::where('charity_transaction_id', $transaction->id)->delete();
            CharityFidyaReceipt::where('charity_transaction_id', $transaction->id)->delete();
            CharityMalReceipt::where('charity_transaction_id', $transaction->id)->delete();
            CharityDonationReceipt::where('charity_transaction_id', $transaction->id)->delete();
            CharityAlmsReceipt::where('charity_transaction_id', $transaction->id)->delete();
            CharityEndowmentReceipt::where('charity_transaction_id', $transaction->id)->delete();
        }

        switch ($type) {
            case 'zakat-fitrah':
                CharityFitrahReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'is_rice' => (bool) ($detail['is_rice'] ?? false),
                    'amount_money' => $detail['amount_money'] ?? null,
                    'amount_rice' => $detail['amount_rice'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
            case 'fidyah':
                CharityFidyaReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'is_rice' => (bool) ($detail['is_rice'] ?? false),
                    'amount_money' => $detail['amount_money'] ?? null,
                    'amount_rice' => $detail['amount_rice'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
            case 'zakat-mal':
                CharityMalReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'amount_money' => $detail['amount_money'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
            case 'infaq':
                CharityDonationReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'amount_money' => $detail['amount_money'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
            case 'sedekah':
                CharityAlmsReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'amount_money' => $detail['amount_money'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
            case 'waqf':
                CharityEndowmentReceipt::create([
                    'charity_transaction_id' => $transaction->id,
                    'item_name' => $detail['item_name'] ?? null,
                    'item_value' => $detail['item_value'] ?? null,
                    'quantity' => $detail['quantity'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
                break;
        }
    }

    protected function syncPackagePayers(CharityTransaction $transaction, array $packagePayers): void
    {
        CharityTransactionPayer::where('charity_transaction_id', $transaction->id)->delete();

        if (! $transaction->is_package) {
            return;
        }

        foreach ($packagePayers as $payer) {
            if (empty($payer['payer_name'])) {
                continue;
            }

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

    protected function summaryCards(): array
    {
        $context = $this->partnerContext();
        $today = now()->toDateString();
        $year = (int) now()->year;
        $currency = $this->currencyCode();

        $todayQuery = $this->transactionBaseQuery($context)
            ->whereDate('charity_transactions.created_at', $today);

        $yearQuery = $this->transactionBaseQuery($context)
            ->whereYear('charity_transactions.created_at', $year);

        $todayTotals = $this->aggregateReceiptTotalsByQuery($todayQuery);
        $yearTotals = $this->aggregateReceiptTotalsByQuery($yearQuery);

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

    protected function transactionBaseQuery(?array $context = null): Builder
    {
        $query = CharityTransaction::query();

        if (! empty($context['organization_id'])) {
            $query->where('charity_transactions.organization_id', $context['organization_id']);
        }

        return $query;
    }

    protected function aggregateReceiptTotalsByQuery(Builder $query): array
    {
        $transactionIds = (clone $query)
            ->select('charity_transactions.id')
            ->distinct()
            ->pluck('charity_transactions.id')
            ->all();

        if (empty($transactionIds)) {
            return [
                'total_money' => 0.0,
                'total_rice' => 0.0,
                'count' => 0,
            ];
        }

        $totalMoney = 0.0;
        $totalRice = 0.0;

        $totalMoney += (float) DB::table('charity_fitrah_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_fidya_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_mal_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_donation_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_alms_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');

        $totalRice += (float) DB::table('charity_fitrah_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_rice');
        $totalRice += (float) DB::table('charity_fidya_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_rice');

        return [
            'total_money' => $totalMoney,
            'total_rice' => $totalRice,
            'count' => count($transactionIds),
        ];
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

        $computedTotalMoney = $inputTotalMoney;

        if (! $data['is_package']) {
            $data['use_same_package_amount'] = false;
            $data['package_amount_each'] = null;
            $data['package_members_count'] = null;
            $packagePayers = [];
            $isInputFamilyMembers = false;
            $representativeTotalMoney = null;
            $this->assertAmountWithinCharityRule($charityType, $computedTotalMoney, 'total_money');
        } else {
            $data['use_same_package_amount'] = (bool) ($data['use_same_package_amount'] ?? false);
            if ($data['use_same_package_amount']) {
                $data['package_amount_each'] = (float) ($data['package_amount_each'] ?? 0);
                $this->assertAmountWithinCharityRule($charityType, $data['package_amount_each'], 'package_amount_each');

                if ($isInputFamilyMembers) {
                    if (count($packagePayers) < 1) {
                        throw ValidationException::withMessages([
                            'package_payers' => __('validation.required', ['attribute' => __('messages.package_payers')]),
                        ]);
                    }
                    $this->validatePackagePayers($packagePayers, requireAmount: false);
                    $data['package_members_count'] = count($packagePayers) + 1;
                } else {
                    $membersCount = $data['package_members_count'] ?? null;
                    if ($membersCount === null || (int) $membersCount < 1) {
                        throw ValidationException::withMessages([
                            'package_members_count' => __('validation.required', ['attribute' => __('messages.family_members_count')]),
                        ]);
                    }
                    $data['package_members_count'] = max(1, (int) $membersCount);
                    $packagePayers = [];
                }

                $normalizedPayers = collect($packagePayers)
                    ->map(function (array $payer) use ($data) {
                        $payer['total_money'] = $data['package_amount_each'];

                        return $payer;
                    })
                    ->toArray();

                $computedTotalMoney = round($data['package_amount_each'] * (float) $data['package_members_count'], 2);
                $packagePayers = $normalizedPayers;
            } else {
                $data['package_amount_each'] = null;
                $isInputFamilyMembers = true;

                if ($representativeTotalMoney === null || $representativeTotalMoney <= 0) {
                    throw ValidationException::withMessages([
                        'representative_total_money' => __('validation.required', ['attribute' => __('messages.representative_total_money')]),
                    ]);
                }

                $this->assertAmountWithinCharityRule($charityType, $representativeTotalMoney, 'representative_total_money');

                if (count($packagePayers) < 1) {
                    throw ValidationException::withMessages([
                        'package_payers' => __('validation.required', ['attribute' => __('messages.package_payers')]),
                    ]);
                }

                $this->validatePackagePayers($packagePayers, requireAmount: true, charityType: $charityType);
                $data['package_members_count'] = count($packagePayers) + 1;

                $familyMembersTotal = collect($packagePayers)
                    ->sum(fn (array $payer) => (float) ($payer['total_money'] ?? 0));
                $computedTotalMoney = round($representativeTotalMoney + $familyMembersTotal, 2);
            }
        }

        $supportsRice = $this->isRiceCapableType($charityType->source?->slug);
        $detail = $this->normalizedDetailFromInput(
            detailInput: $detailInput,
            supportsRice: $supportsRice,
            totalMoney: $computedTotalMoney,
            totalRice: $inputTotalRice,
            notes: $inputNotes
        );

        return [$data, $detail, $packagePayers];
    }

    protected function normalizedDetailFromInput(
        array $detailInput,
        bool $supportsRice,
        ?float $totalMoney,
        ?float $totalRice,
        ?string $notes
    ): array {
        $amountMoney = $totalMoney !== null ? round($totalMoney, 2) : null;
        $amountRice = $totalRice !== null ? round($totalRice, 2) : null;
        $isRice = $supportsRice ? (bool) ($detailInput['is_rice'] ?? false) : false;

        if ($amountMoney !== null && $amountMoney <= 0) {
            $amountMoney = null;
        }

        if ($amountRice !== null && $amountRice <= 0) {
            $amountRice = null;
        }

        if (! $supportsRice) {
            $isRice = false;
            $amountRice = null;
        }

        if (! $isRice) {
            $amountRice = null;
        }

        if ($isRice && ! $amountRice) {
            throw ValidationException::withMessages([
                'total_rice' => __('validation.required', ['attribute' => __('messages.total_rice')]),
            ]);
        }

        if (! $amountMoney && ! $amountRice) {
            throw ValidationException::withMessages([
                'total_money' => __('validation.required_without', ['attribute' => __('messages.total_money'), 'values' => __('messages.total_rice')]),
            ]);
        }

        return [
            'is_rice' => $isRice,
            'amount_money' => $amountMoney,
            'amount_rice' => $isRice ? $amountRice : null,
            'notes' => $notes,
            'item_name' => $detailInput['item_name'] ?? null,
            'item_value' => isset($detailInput['item_value']) ? (float) $detailInput['item_value'] : null,
            'quantity' => isset($detailInput['quantity']) ? (int) $detailInput['quantity'] : null,
        ];
    }

    protected function assertAmountWithinCharityRule(CharityType $charityType, ?float $amount, string $field): void
    {
        if ($amount === null) {
            return;
        }

        $minAmount = $charityType->min_amount !== null ? (float) $charityType->min_amount : null;
        $maxAmount = $charityType->max_amount !== null ? (float) $charityType->max_amount : null;

        if ($minAmount !== null && $amount < $minAmount) {
            throw ValidationException::withMessages([
                $field => __('messages.amount_must_be_at_least', ['amount' => $this->formatMoney($minAmount)]),
            ]);
        }

        if ($maxAmount !== null && $amount > $maxAmount) {
            throw ValidationException::withMessages([
                $field => __('messages.amount_must_not_exceed', ['amount' => $this->formatMoney($maxAmount)]),
            ]);
        }
    }

    protected function validatePackagePayers(array $packagePayers, bool $requireAmount, ?CharityType $charityType = null): void
    {
        foreach ($packagePayers as $index => $payer) {
            $name = trim((string) ($payer['payer_name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    "package_payers.{$index}.payer_name" => __('validation.required', ['attribute' => __('messages.name')]),
                ]);
            }

            if ($requireAmount) {
                $amount = isset($payer['total_money']) ? (float) $payer['total_money'] : null;
                if ($amount === null || $amount <= 0) {
                    throw ValidationException::withMessages([
                        "package_payers.{$index}.total_money" => __('validation.required', ['attribute' => __('messages.total_money')]),
                    ]);
                }

                if ($charityType) {
                    $this->assertAmountWithinCharityRule($charityType, $amount, "package_payers.{$index}.total_money");
                }
            }
        }
    }

    protected function detailPayloadForForm(CharityTransaction $transaction): array
    {
        $notes = $transaction->detailNotes();

        return [
            'is_rice' => $transaction->detailIsRice(),
            'total_money' => $transaction->detailMoneyAmount(),
            'total_rice' => $transaction->detailRiceAmount(),
            'notes' => $notes,
            'detail' => [
                'is_rice' => $transaction->detailIsRice(),
            ],
        ];
    }

    protected function isRiceCapableType(?string $slug): bool
    {
        return in_array($slug, ['zakat-fitrah', 'fidyah'], true);
    }

    protected function partnerContext(): ?array
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
