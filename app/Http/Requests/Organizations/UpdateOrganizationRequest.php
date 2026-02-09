<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organizations\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')->id;

        return [
            'organization_category_id' => ['nullable', 'exists:organization_categories,id'],
            'country_id' => ['required', 'exists:loc_countries,id'],
            'province_id' => ['required', 'exists:loc_provinces,id'],
            'city_id' => ['required', 'exists:loc_cities,id'],
            'district_id' => ['required', 'exists:loc_districts,id'],
            'village_id' => ['required', 'exists:loc_villages,id'],
            'citizens_association_id' => ['nullable', 'exists:loc_citizens_associations,id'],
            'neighborhood_association_id' => ['nullable', 'exists:loc_neighborhood_associations,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organizationId)],
            'type' => ['required', Rule::in([
                Organization::TYPE_RT,
                Organization::TYPE_MOSQUE,
                Organization::TYPE_UMKM,
                Organization::TYPE_INSTITUTION,
            ])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url'],
            'timezone' => ['required', 'string', 'max:100'],
            'settings' => ['nullable', 'array'],
            'profile.description' => ['nullable', 'string'],
            'profile.address_line' => ['nullable', 'string', 'max:255'],
        ];
    }
}
