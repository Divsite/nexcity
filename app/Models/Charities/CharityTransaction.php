<?php

namespace App\Models\Charities;

use App\Models\CharityPayments\CharityPayment;
use App\Models\CharityTypes\CharityType;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharityTransaction extends Model
{
    use HasFactory;

    protected $table = 'charity_transactions';

    protected $guarded = ['id'];

    protected $casts = [
        'is_package' => 'boolean',
        'use_same_package_amount' => 'boolean',
        'package_amount_each' => 'decimal:2',
        'package_members_count' => 'integer',
    ];

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
            ($this->almsReceipt?->amount_money ?? 0)
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
}
