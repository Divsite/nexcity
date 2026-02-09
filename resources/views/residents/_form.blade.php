@csrf

<template v-if="isPartner && mode === 'create'">
    <div class="row g-3 mt-2">
        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.name"
                       :class="['form-control', errors.name ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.username') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" :value="usernamePreview" disabled
                       :class="['form-control', errors.username ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.username"><strong>@{{ errors.username[0] }}</strong></span>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.address') }}</label>
                <textarea class="form-control" rows="3" v-model="form.profile.address_line"
                          :class="['form-control', errors['profile.address_line'] ? 'is-invalid' : '']"></textarea>
                <span class="invalid-feedback" v-if="errors['profile.address_line']">
                    <strong>@{{ errors['profile.address_line'][0] }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<template v-else>
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.name"
                   :class="['form-control', errors.name ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.email') }} <span class="text-danger">*</span></label>
            <input type="email" class="form-control" v-model="form.email"
                   :class="['form-control', errors.email ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.username') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.username"
                   :class="['form-control', errors.username ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.username"><strong>@{{ errors.username[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.phone_number') }}</label>
            <input type="text" class="form-control" v-model="form.phone"
                   :class="['form-control', errors.phone ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.phone"><strong>@{{ errors.phone[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.organization') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.organization_id" v-select2
                    :class="['form-select', errors['profile.organization_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="organization in options.organizations" :key="organization.id" :value="organization.id">
                    @{{ organization.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.organization_id']">
                <strong>@{{ errors['profile.organization_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6" v-if="mode === 'create'">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.password') }}</label>
            <input type="password" class="form-control" v-model="form.password"
                   :class="['form-control', errors.password ? 'is-invalid' : '']"
                   placeholder="{{ __('messages.optional') }}">
            <span class="invalid-feedback" v-if="errors.password"><strong>@{{ errors.password[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.national_id') }}</label>
            <input type="text" class="form-control" v-model="form.profile.national_id_number"
                   :class="['form-control', errors['profile.national_id_number'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.national_id_number']">
                <strong>@{{ errors['profile.national_id_number'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.family_card_number') }}</label>
            <input type="text" class="form-control" v-model="form.profile.family_card_number"
                   :class="['form-control', errors['profile.family_card_number'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.family_card_number']">
                <strong>@{{ errors['profile.family_card_number'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.birth_place') }}</label>
            <input type="text" class="form-control" v-model="form.profile.birth_place"
                   :class="['form-control', errors['profile.birth_place'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.birth_place']">
                <strong>@{{ errors['profile.birth_place'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.birth_date') }}</label>
            <input type="date" class="form-control" v-model="form.profile.birth_date"
                   :class="['form-control', errors['profile.birth_date'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.birth_date']">
                <strong>@{{ errors['profile.birth_date'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.gender') }}</label>
            <select class="form-select select2" v-model="form.profile.gender" v-select2
                    :class="['form-select', errors['profile.gender'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="gender in options.genders" :key="gender.value" :value="gender.value">
                    @{{ gender.label }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.gender']">
                <strong>@{{ errors['profile.gender'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.residence_status') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.residence_status_id" v-select2
                    :class="['form-select', errors['profile.residence_status_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="status in options.residence_statuses" :key="status.id" :value="status.id">
                    @{{ status.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.residence_status_id']">
                <strong>@{{ errors['profile.residence_status_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.marital_status') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.marital_status_id" v-select2
                    :class="['form-select', errors['profile.marital_status_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="status in options.marital_statuses" :key="status.id" :value="status.id">
                    @{{ status.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.marital_status_id']">
                <strong>@{{ errors['profile.marital_status_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.religion') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.religion_id" v-select2
                    :class="['form-select', errors['profile.religion_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="religion in options.religions" :key="religion.id" :value="religion.id">
                    @{{ religion.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.religion_id']">
                <strong>@{{ errors['profile.religion_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.education') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.education_id" v-select2
                    @change="handleEducationChange"
                    :class="['form-select', errors['profile.education_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="education in options.educations" :key="education.id" :value="education.id">
                    @{{ education.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.education_id']">
                <strong>@{{ errors['profile.education_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.education_major') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.education_major_id" v-select2
                    :class="['form-select', errors['profile.education_major_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="major in filteredEducationMajors" :key="major.id" :value="major.id">
                    @{{ major.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.education_major_id']">
                <strong>@{{ errors['profile.education_major_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.occupation') }}</label>
            <input type="text" class="form-control" v-model="form.profile.occupation"
                   :class="['form-control', errors['profile.occupation'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.occupation']">
                <strong>@{{ errors['profile.occupation'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="is_head_family"
                   v-model="form.profile.is_head_family">
            <label class="form-check-label" for="is_head_family">{{ __('messages.is_head_family') }}</label>
        </div>
        <span class="invalid-feedback d-block" v-if="errors['profile.is_head_family']">
            <strong>@{{ errors['profile.is_head_family'][0] }}</strong>
        </span>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.family_members_count') }}</label>
            <input type="number" class="form-control" v-model="form.profile.family_members_count" min="0"
                   :class="['form-control', errors['profile.family_members_count'] ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors['profile.family_members_count']">
                <strong>@{{ errors['profile.family_members_count'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.interests') }}</label>
            <textarea class="form-control" rows="2" v-model="interestsText"
                      :class="['form-control', errors['profile.interests'] ? 'is-invalid' : '']"
                      placeholder="contoh: olahraga, musik, desain"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.interests']">
                <strong>@{{ errors['profile.interests'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.talents') }}</label>
            <textarea class="form-control" rows="2" v-model="talentsText"
                      :class="['form-control', errors['profile.talents'] ? 'is-invalid' : '']"
                      placeholder="contoh: public speaking, coding, memasak"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.talents']">
                <strong>@{{ errors['profile.talents'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.ktp_photo') }}</label>
            <input type="text" class="form-control" v-model="form.profile.ktp_photo_path"
                   :class="['form-control', errors['profile.ktp_photo_path'] ? 'is-invalid' : '']"
                   placeholder="uploads/ktp/filename.jpg">
            <span class="invalid-feedback" v-if="errors['profile.ktp_photo_path']">
                <strong>@{{ errors['profile.ktp_photo_path'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.house_photos') }}</label>
            <textarea class="form-control" rows="2" v-model="housePhotosText"
                      :class="['form-control', errors['profile.house_photo_paths'] ? 'is-invalid' : '']"
                      placeholder="uploads/house/a.jpg, uploads/house/b.jpg"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.house_photo_paths']">
                <strong>@{{ errors['profile.house_photo_paths'][0] }}</strong>
            </span>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.country') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.country_id" @change="handleCountryChange" v-select2
                    :class="['form-select', errors['profile.country_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="country in options.countries" :key="country.id" :value="country.id">
                    @{{ country.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.country_id']">
                <strong>@{{ errors['profile.country_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.province') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.province_id" @change="handleProvinceChange" v-select2
                    :class="['form-select', errors['profile.province_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="province in locationOptions.provinces" :key="province.id" :value="province.id">
                    @{{ province.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.province_id']">
                <strong>@{{ errors['profile.province_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.city') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.city_id" @change="handleCityChange" v-select2
                    :class="['form-select', errors['profile.city_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="city in locationOptions.cities" :key="city.id" :value="city.id">
                    @{{ city.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.city_id']">
                <strong>@{{ errors['profile.city_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.district') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.district_id" @change="handleDistrictChange" v-select2
                    :class="['form-select', errors['profile.district_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="district in locationOptions.districts" :key="district.id" :value="district.id">
                    @{{ district.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.district_id']">
                <strong>@{{ errors['profile.district_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.village') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.village_id" @change="handleVillageChange" v-select2
                    :class="['form-select', errors['profile.village_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="village in locationOptions.villages" :key="village.id" :value="village.id">
                    @{{ village.name }} (@{{ village.postal_code }})
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.village_id']">
                <strong>@{{ errors['profile.village_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.citizens_association') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.citizens_association_id" @change="handleCitizensChange" v-select2
                    :class="['form-select', errors['profile.citizens_association_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="citizen in locationOptions.citizens" :key="citizen.id" :value="citizen.id">
                    @{{ citizen.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.citizens_association_id']">
                <strong>@{{ errors['profile.citizens_association_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.neighborhood_association') }} <span class="text-danger">*</span></label>
            <select class="form-select select2" v-model="form.profile.neighborhood_association_id" v-select2
                    :class="['form-select', errors['profile.neighborhood_association_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="neighborhood in locationOptions.neighborhoods" :key="neighborhood.id"
                        :value="neighborhood.id">
                    @{{ neighborhood.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['profile.neighborhood_association_id']">
                <strong>@{{ errors['profile.neighborhood_association_id'][0] }}</strong>
            </span>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.address') }}</label>
            <textarea class="form-control" rows="3" v-model="form.profile.address_line"
                      :class="['form-control', errors['profile.address_line'] ? 'is-invalid' : '']"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.address_line']">
                <strong>@{{ errors['profile.address_line'][0] }}</strong>
            </span>
        </div>
    </div>
</div>
</template>

<div class="text-end mt-3">
    <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
            @click.prevent="submitForm()" :key="submit_form_key">
        <span class="d-flex justify-content-center">
            <span class="spinner-border" role="status" v-if="loading">
                <span class="visually-hidden">{{ __('messages.loading') }}</span>
            </span>
            <span :class="[loading ? 'ms-2' : '']">
                {{ __('messages.save') }}
            </span>
        </span>
    </button>
</div>
