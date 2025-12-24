<?php

namespace App\Models\FormProcesses;

use App\Models\Forms\Form;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class FormProcessStatus extends Model
{
    use HasFactory, CreatedUpdatedBy, SoftDeletes;

    const ACTIVE_KEY = "active";
    const INACTIVE_KEY = "inactive";

    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function statusName()
    {
        if ($this->status) {
            return __('messages.active');
        }

        return __('messages.inactive');
    }

    public static function statusList(): array
    {
        return [
            'all' => 'all',
            self::ACTIVE_KEY => self::ACTIVE_KEY,
            self::INACTIVE_KEY => self::INACTIVE_KEY,
        ];
    }

    public static function statusItems(): Collection
    {
        return collect([
            'all' => __('messages.all'),
            self::ACTIVE_KEY => __('messages.active'),
            self::INACTIVE_KEY => __('messages.inactive')
        ]);
    }
}
