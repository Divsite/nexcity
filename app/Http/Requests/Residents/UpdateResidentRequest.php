<?php

namespace App\Http\Requests\Residents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $residentId = $this->route('resident')->id;
        $isPartnerContext = $this->isPartnerContext();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [$isPartnerContext ? 'nullable' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($residentId)],
            'username' => [$isPartnerContext ? 'nullable' : 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($residentId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'profile.organization_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:organizations,id'],
            'profile.national_id_number' => ['nullable', 'string', 'max:50'],
            'profile.family_card_number' => ['nullable', 'string', 'max:50'],
            'profile.birth_place' => ['nullable', 'string', 'max:100'],
            'profile.birth_date' => ['nullable', 'date'],
            'profile.gender' => ['nullable', Rule::in(['male', 'female'])],
            'profile.residence_status_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:m_residence_statuses,id'],
            'profile.marital_status_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:m_marital_statuses,id'],
            'profile.education_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:m_educations,id'],
            'profile.education_major_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:m_education_majors,id'],
            'profile.religion_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:m_religions,id'],
            'profile.occupation' => ['nullable', 'string', 'max:50'],
            'profile.is_head_family' => ['nullable', 'boolean'],
            'profile.family_members_count' => ['nullable', 'integer', 'min:0'],
            'profile.interests' => ['nullable', 'array'],
            'profile.talents' => ['nullable', 'array'],
            'profile.ktp_photo_path' => ['nullable', 'string', 'max:255'],
            'profile.house_photo_paths' => ['nullable', 'array'],
            'profile.address_line' => ['nullable', 'string', 'max:255'],
            'profile.country_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_countries,id'],
            'profile.province_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_provinces,id'],
            'profile.city_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_cities,id'],
            'profile.district_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_districts,id'],
            'profile.village_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_villages,id'],
            'profile.citizens_association_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_citizens_associations,id'],
            'profile.neighborhood_association_id' => [$isPartnerContext ? 'nullable' : 'required', 'exists:loc_neighborhood_associations,id'],
        ];
    }

    private function isPartnerContext(): bool
    {
        $user = $this->user();

        if (! $user || $user->hasRole('superadmin')) {
            return false;
        }

        return $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'rt-%')
            ->exists();
    }
}
