<?php

namespace App\Service\Forms;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ConditionalRule
{
    public array $property;
    public array $formData;

    public function __construct(array $property, array $formData)
    {
        $this->property = $property;
        $this->formData = $formData;
    }

    public function conditionMet(): bool
    {
        $globalOperator = $this->property['logic']['conditions']['operator'];

        $conditionGroup = [];

        $groups = $this->property['logic']['conditions']['group'];

        foreach ($groups as $group) {
            $groupOperator = $group['operator'];

            $conditionRules = [];

            $rules = $group['rules'];

            foreach ($rules as $rule) {
                $field = collect($this->formData);

                if ($field->has($rule['property_id'])) {
                    $conditionRules[] = $this->ruleMet($rule);
                }
            }

            $conditionGroup[] = $this->checkConditionMet($conditionRules, $groupOperator);
        }

        return $this->checkConditionMet($conditionGroup, $globalOperator);
    }

    private function checkConditionMet(array $group, string $operator): bool
    {
        $conditionOperator = conditional_rule_operator();

        $group = collect($group);

        if ($operator == $conditionOperator['all']) {
            return $group->every(function ($value) {
                return $value == true;
            });
        }

        if ($operator == $conditionOperator['any']) {
            return $group->some(function ($value) {
                return $value == true;
            });
        }

        return false;
    }

    private function ruleMet(array $rule): ?bool
    {
        $compare = comparison_operator();

        return match ($rule['compare']) {
            $compare['is_present'] => $this->isPresent($rule),
            $compare['is_blank'] => $this->isBlank($rule),
            $compare['is'] => $this->is($rule),
            $compare['is_not'] => $this->isNot($rule),
            $compare['contains'] => $this->contains($rule),
            $compare['does_not_contains'] => $this->doesNotContains($rule),
            $compare['starts_with'] => $this->startsWith($rule),
            $compare['ends_with'] => $this->endsWith($rule),
            $compare['is_before'] => $this->isBefore($rule),
            $compare['is_after'] => $this->isAfter($rule),
            $compare['is_checked'] => $this->isChecked($rule),
            $compare['is_not_checked'] => $this->isNotChecked($rule),
            $compare['has_file_selected'] => $this->hasFileSelected($rule),
            $compare['has_no_file_selected'] => $this->hasNoFileSelected($rule),
            default => false,
        };

    }

    private function isPresent($rule): bool
    {
        return !!$this->formData[$rule['property_id']];
    }

    private function isBlank($rule): bool
    {
        return !$this->formData[$rule['property_id']];
    }

    private function is($rule): bool
    {
        return $rule['value'] == $this->formData[$rule['property_id']];
    }

    private function isNot($rule): bool
    {
        return $rule['value'] != $this->formData[$rule['property_id']];
    }

    private function contains($rule): bool
    {
        return $this->formData[$rule['property_id']] && Str::contains($this->formData[$rule['property_id']],
                $rule['value']);
    }

    private function doesNotContains($rule): bool
    {
        return $this->formData[$rule['property_id']] && !Str::contains($this->formData[$rule['property_id']],
                $rule['value']);
    }

    private function startsWith($rule): bool
    {
        return $this->formData[$rule['property_id']] && Str::of($this->formData[$rule['property_id']])->startsWith($rule['value']);
    }

    private function endsWith($rule): bool
    {
        return $this->formData[$rule['property_id']] && Str::of($this->formData[$rule['property_id']])->endsWith($rule['value']);
    }

    private function isBefore($rule): bool
    {
        if ($this->formData[$rule['property_id']]) {
            $ruleValue = Carbon::createFromFormat('Y-m-d', $rule['value']);
            return Carbon::createFromFormat('Y-m-d', $this->formData[$rule['property_id']])->isBefore($ruleValue);
        }

        return false;
    }

    private function isAfter($rule): bool
    {
        if ($this->formData[$rule['property_id']]) {
            $ruleValue = Carbon::createFromFormat('Y-m-d', $rule['value']);
            return Carbon::createFromFormat('Y-m-d', $this->formData[$rule['property_id']])->isAfter($ruleValue);
        }

        return false;
    }

    private function isChecked($rule): bool
    {
        if (!isset($this->formData[$rule['property_id']])) {
            return false;
        }

        $value = $this->formData[$rule['property_id']];

        // Checkbox Group
        if (is_array($value)) {
            return in_array($rule['value'], $value);
        }

        // Single checkbox
        return $value == true;
    }

    private function isNotChecked($rule): bool
    {
        if (!isset($this->formData[$rule['property_id']])) {
            return false;
        }

        $value = $this->formData[$rule['property_id']];

        // Checkbox Group
        if (is_array($value)) {
            return in_array($rule['value'], $value);
        }

        // Single checkbox
        return $value == false;
    }

    private function hasFileSelected($rule): bool
    {
        return count($this->formData[$rule['property_id']]) > 0;
    }

    private function hasNoFileSelected($rule): bool
    {
        return count($this->formData[$rule['property_id']]) == 0;
    }
}
