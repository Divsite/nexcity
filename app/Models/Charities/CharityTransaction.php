<?php

namespace App\Models\Charities;

use App\Models\CharityPayments\CharityPayment;
use App\Models\CharityTypes\CharityType;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CharityTransaction extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_TRANSFER = 'transfer';
    public const PAYMENT_METHOD_QRIS = 'qris';

    protected $table = 'charity_transactions';

    protected $guarded = ['id'];

    protected $casts = [
        'is_package' => 'boolean',
        'use_same_package_amount' => 'boolean',
        'package_amount_each' => 'decimal:2',
        'package_members_count' => 'integer',
        'is_input_family_members' => 'boolean',
        'representative_total_money' => 'decimal:2',
        'multiplier_count' => 'integer',
    ];

    public function scopeForOrganization(Builder $query, ?int $organizationId): Builder
    {
        if ($organizationId) {
            $query->where('charity_transactions.organization_id', $organizationId);
        }

        return $query;
    }

    public function scopeForCharityType(Builder $query, ?int $charityTypeId): Builder
    {
        if ($charityTypeId) {
            $query->where('charity_transactions.charity_type_id', $charityTypeId);
        }

        return $query;
    }

    public function scopeForPaymentMethod(Builder $query, ?string $paymentMethod): Builder
    {
        if ($paymentMethod) {
            $query->where('charity_transactions.payment_method', $paymentMethod);
        }

        return $query;
    }

    public function scopeWithCharityRelations(Builder $query): Builder
    {
        return $query->with([
            'charityType.source',
            'organization',
            'receivedBy',
            'fitrahReceipt',
            'fidyaReceipt',
            'malReceipt',
            'donationReceipt',
            'almsReceipt',
            'endowmentReceipt',
        ]);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('charity_transactions.status', self::STATUS_PAID);
    }

    public function scopeCreatedOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('charity_transactions.created_at', $date);
    }

    public function scopeCreatedInYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('charity_transactions.created_at', $year);
    }

    public function scopeCreatedBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query
            ->whereDate('charity_transactions.created_at', '>=', $startDate)
            ->whereDate('charity_transactions.created_at', '<=', $endDate);
    }

    public static function paymentMethodLabels(): array
    {
        return [
            self::PAYMENT_METHOD_CASH => __('messages.cash'),
            self::PAYMENT_METHOD_TRANSFER => __('messages.transfer'),
            self::PAYMENT_METHOD_QRIS => __('messages.qris'),
        ];
    }

    public static function paymentMethodOptions(bool $includeAll = false): array
    {
        $options = [];

        if ($includeAll) {
            $options[] = ['value' => '', 'label' => __('messages.all')];
        }

        foreach (self::paymentMethodLabels() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function charityType(): BelongsTo
    {
        return $this->belongsTo(CharityType::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(CharityPayment::class, 'charity_payment_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function payers(): HasMany
    {
        return $this->hasMany(CharityTransactionPayer::class, 'charity_transaction_id');
    }

    public function packagePayers(): HasMany
    {
        return $this->payers();
    }

    public function fitrahReceipt(): HasOne
    {
        return $this->hasOne(CharityFitrahReceipt::class, 'charity_transaction_id');
    }

    public function fidyaReceipt(): HasOne
    {
        return $this->hasOne(CharityFidyaReceipt::class, 'charity_transaction_id');
    }

    public function malReceipt(): HasOne
    {
        return $this->hasOne(CharityMalReceipt::class, 'charity_transaction_id');
    }

    public function donationReceipt(): HasOne
    {
        return $this->hasOne(CharityDonationReceipt::class, 'charity_transaction_id');
    }

    public function almsReceipt(): HasOne
    {
        return $this->hasOne(CharityAlmsReceipt::class, 'charity_transaction_id');
    }

    public function endowmentReceipt(): HasOne
    {
        return $this->hasOne(CharityEndowmentReceipt::class, 'charity_transaction_id');
    }

    public function detailMoneyAmount(): float
    {
        return (float) (
            ($this->fitrahReceipt?->amount_money ?? 0) +
            ($this->fidyaReceipt?->amount_money ?? 0) +
            ($this->malReceipt?->amount_money ?? 0) +
            ($this->donationReceipt?->amount_money ?? 0) +
            ($this->almsReceipt?->amount_money ?? 0) +
            ($this->endowmentReceipt?->amount_money ?? 0)
        );
    }

    public function detailRiceAmount(): float
    {
        return (float) (
            ($this->fitrahReceipt?->amount_rice ?? 0) +
            ($this->fidyaReceipt?->amount_rice ?? 0)
        );
    }

    public function detailNotes(): ?string
    {
        return $this->fitrahReceipt?->notes
            ?? $this->fidyaReceipt?->notes
            ?? $this->malReceipt?->notes
            ?? $this->donationReceipt?->notes
            ?? $this->almsReceipt?->notes
            ?? $this->endowmentReceipt?->notes;
    }

    public function detailIsRice(): bool
    {
        return (bool) ($this->fitrahReceipt?->is_rice || $this->fidyaReceipt?->is_rice);
    }

    public function statusLabel(): string
    {
        $status = (string) $this->status;
        $translated = __('messages.' . $status);

        if ($translated !== 'messages.' . $status) {
            return $translated;
        }

        return Str::title(str_replace('_', ' ', $status));
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'badge bg-success-subtle text-success',
            self::STATUS_DRAFT => 'badge bg-warning-subtle text-warning',
            self::STATUS_CANCELLED => 'badge bg-danger-subtle text-danger',
            default => 'badge bg-secondary-subtle text-secondary',
        };
    }
}
