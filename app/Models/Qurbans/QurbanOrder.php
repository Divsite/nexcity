<?php

namespace App\Models\Qurbans;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_TYPE_ADMIN = 'admin';
    public const SOURCE_TYPE_RESIDENT = 'resident';
    public const SOURCE_TYPE_PUBLIC = 'public';

    protected $guarded = ['id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(QurbanProgram::class, 'qurban_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QurbanOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(QurbanOrderPayment::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(QurbanWorkflowLog::class);
    }

    /**
     * What this order costs, what has come in, and what is left.
     *
     * One calculation for the pengurus's instalment sheet and the warga's own
     * "Qurban saya": two copies would drift, and the two people would argue
     * over different figures for the same line.
     *
     * @return array{target: float, paid: float, remaining: float, is_settled: bool}
     */
    public function paymentSummary(): array
    {
        $this->loadMissing(['items', 'payments']);

        $target = (float) $this->items->sum('subtotal');
        $paid = (float) $this->payments
            ->where('status', '!=', 'cancelled')
            ->sum('amount');

        return [
            'target' => $target,
            'paid' => $paid,
            // Never below zero. An overpayment is a bookkeeping matter, not a
            // negative amount owed, and showing one would read as a refund due.
            'remaining' => max(0.0, $target - $paid),
            'is_settled' => $paid >= $target && $target > 0,
        ];
    }
}
