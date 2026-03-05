<?php

namespace App\Http\Requests\Distributions;

use App\Models\DistributionClasses\DistributionClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'distribution_class_id' => ['required', 'exists:distribution_classes,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'country_id' => ['nullable', 'exists:loc_countries,id'],
            'province_id' => ['nullable', 'exists:loc_provinces,id'],
            'city_id' => ['nullable', 'exists:loc_cities,id'],
            'district_id' => ['nullable', 'exists:loc_districts,id'],
            'village_id' => ['nullable', 'exists:loc_villages,id'],
            'citizens_association_id' => ['nullable', 'exists:loc_citizens_associations,id'],
            'neighborhood_association_id' => ['nullable', 'exists:loc_neighborhood_associations,id'],
            'officer_ids' => ['nullable', 'array'],
            'officer_ids.*' => ['integer', 'exists:users,id'],
            'use_manual_recipients' => ['nullable', 'boolean'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
            'manual_recipients' => ['nullable', 'array'],
            'manual_recipients.*.name' => ['required_if:use_manual_recipients,1', 'string', 'max:255'],
            'manual_recipients.*.phone' => ['nullable', 'string', 'max:50'],
            'manual_recipients.*.address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $classId = $this->input('distribution_class_id');
            $distributionClass = $classId ? DistributionClass::query()->find($classId) : null;
            $isInternal = (bool) ($distributionClass?->is_internal);
            $useManual = $this->boolean('use_manual_recipients');
            $recipientIds = $this->input('recipient_ids', []);
            $manualRecipients = $this->input('manual_recipients', []);
            $neighborhood = $this->input('neighborhood_association_id');
            $officerIds = $this->input('officer_ids', []);

            if (empty($officerIds)) {
                $validator->errors()->add('officer_ids', __('messages.recipients_required'));
            }

            if ($useManual && empty($manualRecipients)) {
                $validator->errors()->add('manual_recipients', __('messages.manual_recipients_required'));
            }

            if (! $useManual && empty($recipientIds)) {
                $validator->errors()->add('recipient_ids', __('messages.recipients_required'));
            }

            if (! $useManual && empty($neighborhood)) {
                $validator->errors()->add('neighborhood_association_id', __('messages.neighborhood_required'));
            }
        });
    }
}
