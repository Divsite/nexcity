<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class DateRange implements ValidationRule
{
    public function __construct(public array $property)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minMaxOptionList = min_max_option_list();

        $dateRangeValue = explode(' - ', $value);

        try {
            $fromDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[0])->startOfDay();

            $toDate = null;
            if (!empty($dateRangeValue[1])) {
                $toDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[1])->startOfDay();
            }

            if ($this->property['disable_past_dates']) {
                $now = Carbon::today();

                if ($fromDate->lessThan($now)) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $now->translatedFormat($this->property['date_format'])
                    ]));
                }

                if ($toDate && $toDate->lessThan($now)) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $now->translatedFormat($this->property['date_format'])
                    ]));
                }
            }

            $minDate = null;
            $maxDate = null;

            if ($this->property['min_max_options'] == $minMaxOptionList['specific_date']) {
                if ($this->property['min_date']) {
                    $minDate = Carbon::createFromFormat('Y-m-d', $this->property['min_date'])->startOfDay();
                }

                if ($this->property['max_date']) {
                    $maxDate = Carbon::createFromFormat('Y-m-d', $this->property['max_date'])->startOfDay();
                }
            }

            if ($this->property['min_max_options'] == $minMaxOptionList['number_days']) {
                if ($this->property['min_number_days']) {
                    $minDate = Carbon::now()->addDays($this->property['min_number_days'])->startOfDay();
                }

                if ($this->property['max_number_days']) {
                    $maxDate = Carbon::now()->addDays($this->property['max_number_days'])->startOfDay();
                }
            }

            if ($minDate) {
                if ($fromDate->lessThan($minDate)) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $minDate->translatedFormat($this->property['date_format'])
                    ]));
                }

                if ($toDate && $toDate->lessThan($minDate)) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $minDate->translatedFormat($this->property['date_format'])
                    ]));
                }
            }

            if ($maxDate) {
                if ($fromDate->greaterThan($maxDate)) {
                    $fail(__('validation.before_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $maxDate->translatedFormat($this->property['date_format'])
                    ]));
                }

                if ($toDate && $toDate->greaterThan($maxDate)) {
                    $fail(__('validation.before_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $maxDate->translatedFormat($this->property['date_format'])
                    ]));
                }
            }

            if (!empty($this->property['disable_dates'])) {
                $isBetween = false;

                $date1 = $fromDate ?: null;
                $date2 = $toDate ?: $fromDate;

                foreach ($this->property['disable_dates'] as $disableDate) {
                    $betweenDate = Carbon::createFromFormat('Y-m-d', $disableDate)->startOfDay();

                    if ($betweenDate->isBetween($date1, $date2)) {
                        $isBetween = true;
                    }
                }

                if ($isBetween) {
                    $fail(__('messages.invalid_date_between', [
                        'attribute' => Str::lower($this->property['label']),
                        'date1' => $date1->translatedFormat($this->property['date_format']),
                        'date2' => $date2->translatedFormat($this->property['date_format']),
                    ]));
                }
            }

            if ($fromDate && $toDate) {
                if ($toDate->lessThan($fromDate)) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => Str::lower($this->property['label']),
                        'date' => $fromDate->translatedFormat($this->property['date_format'])
                    ]));
                }
            }

        } catch (Exception $e) {
            // Date format validate
            $fail(__('validation.date_format', [
                'attributes' => Str::lower($this->property['label']),
                'format' => 'Y-m-d'
            ]));
        }
    }
}
