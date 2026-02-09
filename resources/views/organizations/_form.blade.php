@csrf

<div class="row g-3 mt-2">
    <div class="col-lg-8">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.name"
                   :class="['form-control', errors.name ? 'is-invalid' : '']" placeholder="{{ __('messages.name') }}">
            <span class="invalid-feedback" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.slug') ?? 'Slug' }}</label>
            <input type="text" class="form-control" v-model="form.slug" disabled
                   :class="['form-control', errors.slug ? 'is-invalid' : '']" placeholder="slug">
            <span class="invalid-feedback" v-if="errors.slug"><strong>@{{ errors.slug[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.organization_category') }}</label>
            <select class="form-select" v-model="form.organization_category_id"
                    :class="['form-select', errors['organization_category_id'] ? 'is-invalid' : '']">
                <option value="">{{ __('messages.none') }}</option>
                <option v-for="category in options.categories" :key="category.id" :value="category.id">
                    @{{ category.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors['organization_category_id']">
                <strong>@{{ errors['organization_category_id'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.organization_type') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.type"
                    :class="['form-select', errors.type ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="type in options.types" :key="type.value" :value="type.value">@{{ type.label }}</option>
            </select>
            <span class="invalid-feedback" v-if="errors.type"><strong>@{{ errors.type[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.status"
                    :class="['form-select', errors.status ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="status in options.statuses" :key="status.value" :value="status.value">
                    @{{ status.label }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.status"><strong>@{{ errors.status[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.timezone') ?? 'Timezone' }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.timezone"
                   :class="['form-control', errors.timezone ? 'is-invalid' : '']" placeholder="Asia/Jakarta">
            <span class="invalid-feedback" v-if="errors.timezone"><strong>@{{ errors.timezone[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.email') }}</label>
            <input type="email" class="form-control" v-model="form.email"
                   :class="['form-control', errors.email ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.phone_number') }}</label>
            <input type="text" class="form-control" v-model="form.phone"
                   :class="['form-control', errors.phone ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.phone"><strong>@{{ errors.phone[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" class="form-control" v-model="form.website"
                   :class="['form-control', errors.website ? 'is-invalid' : '']">
            <span class="invalid-feedback" v-if="errors.website"><strong>@{{ errors.website[0] }}</strong></span>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.country') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.country_id" @change="handleCountryChange"
                    :class="['form-select', errors.country_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="country in options.countries" :key="country.id" :value="country.id">
                    @{{ country.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.country_id"><strong>@{{ errors.country_id[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.province') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.province_id" @change="handleProvinceChange"
                    :class="['form-select', errors.province_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="province in locationOptions.provinces" :key="province.id" :value="province.id">
                    @{{ province.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.province_id"><strong>@{{ errors.province_id[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.city') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.city_id" @change="handleCityChange"
                    :class="['form-select', errors.city_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="city in locationOptions.cities" :key="city.id" :value="city.id">
                    @{{ city.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.city_id"><strong>@{{ errors.city_id[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.district') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.district_id" @change="handleDistrictChange"
                    :class="['form-select', errors.district_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="district in locationOptions.districts" :key="district.id" :value="district.id">
                    @{{ district.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.district_id"><strong>@{{ errors.district_id[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.village') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.village_id" @change="handleVillageChange"
                    :class="['form-select', errors.village_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="village in locationOptions.villages" :key="village.id" :value="village.id">
                    @{{ village.name }} (@{{ village.postal_code }})
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.village_id"><strong>@{{ errors.village_id[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.citizens_association') }}</label>
            <select class="form-select" v-model="form.citizens_association_id" @change="handleCitizensChange"
                    :class="['form-select', errors.citizens_association_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="citizen in locationOptions.citizens" :key="citizen.id" :value="citizen.id">
                    @{{ citizen.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.citizens_association_id">
                <strong>@{{ errors.citizens_association_id[0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.neighborhood_association') }}</label>
            <select class="form-select" v-model="form.neighborhood_association_id"
                    :class="['form-select', errors.neighborhood_association_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="neighborhood in locationOptions.neighborhoods" :key="neighborhood.id"
                        :value="neighborhood.id">
                    @{{ neighborhood.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.neighborhood_association_id">
                <strong>@{{ errors.neighborhood_association_id[0] }}</strong>
            </span>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.address') }}</label>
            <textarea class="form-control" rows="3" v-model="form.profile.address_line"
                      :class="['form-control', errors['profile.address_line'] ? 'is-invalid' : '']"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.address_line']">
                <strong>@{{ errors['profile.address_line'][0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.description') }}</label>
            <textarea class="form-control" rows="3" v-model="form.profile.description"
                      :class="['form-control', errors['profile.description'] ? 'is-invalid' : '']"></textarea>
            <span class="invalid-feedback" v-if="errors['profile.description']">
                <strong>@{{ errors['profile.description'][0] }}</strong>
            </span>
        </div>
    </div>
</div>

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
