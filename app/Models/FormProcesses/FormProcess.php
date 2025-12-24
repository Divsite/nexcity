<?php

namespace App\Models\FormProcesses;

use App\Models\Forms\Form;
use App\Models\Users\User;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class FormProcess extends Model
{
    use HasFactory, CreatedUpdatedBy, SoftDeletes;

    const REVERT_SUBMITTER = "submitter";
    const END_PROCESS = "ended";

    const DECISION_TYPE_ANY = "any";
    const DECISION_TYPE_MAJORITY = "majority";
    const DECISION_TYPE_ALL = "all";

    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(FormProcessAction::class, 'process_id', 'id');
    }

    public function processorUsers(): HasMany
    {
        return $this->hasMany(FormProcessUser::class, 'process_id', 'id');
    }

    public function processorRoles(): HasMany
    {
        return $this->hasMany(FormProcessRole::class, 'process_id', 'id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function statusName()
    {
        if ($this->status) {
            return __('messages.active');
        }

        return __('messages.inactive');
    }

    public static function revertEndProcess(): array
    {
        return [
            self::REVERT_SUBMITTER => [
                'id' => self::REVERT_SUBMITTER,
                'name' => __('messages.return_to_sender'),
            ],
            self::END_PROCESS => [
                'id' => self::END_PROCESS,
                'name' => __('messages.end_process'),
            ],
        ];
    }

    public static function decisionTypeList(): Collection
    {
        return collect([
            self::DECISION_TYPE_ANY => __('messages.any_first_user_can_decide'),
            self::DECISION_TYPE_MAJORITY => __('messages.majority_user_can_decide'),
            self::DECISION_TYPE_ALL => __('messages.all_users_must_decide'),
        ]);
    }

    public static function decisionTypeItems(): Collection
    {
        return collect([
            self::DECISION_TYPE_ANY => self::DECISION_TYPE_ANY,
            self::DECISION_TYPE_MAJORITY => self::DECISION_TYPE_MAJORITY,
            self::DECISION_TYPE_ALL => self::DECISION_TYPE_ALL,
        ]);
    }
}
