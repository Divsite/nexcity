<form @submit.prevent="submitForm()">
    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label">{{ __('messages.distribution_class') }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.distribution_class_id">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="item in options.distribution_classes" :key="item.id" :value="item.id">
                    @{{ item.name }} (@{{ item.year }})
                </option>
            </select>
            <span class="invalid-feedback d-block" v-if="errors.distribution_class_id">
                <strong>@{{ errors.distribution_class_id[0] }}</strong>
            </span>
        </div>
        <div class="col-lg-6">
            <label class="form-label">{{ __('messages.year') }}</label>
            <input type="text" class="form-control" v-model="form.year" disabled>
        </div>
    </div>

    <div v-if="selectedClass" class="alert alert-info mt-3 mb-0">
        <div class="fw-semibold mb-1">{{ __('messages.distribution_rules') }}</div>
        <div class="small">
            {{ __('messages.money_amount') }}: @{{ formatMoney(selectedClass.get_money) }}
            · {{ __('messages.total_rice') }}: @{{ formatRice(selectedClass.get_rice) }}
        </div>
    </div>

    <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">{{ __('messages.officers') }}</h6>
        </div>
        <select class="form-select" multiple v-model="form.officer_ids">
            <option v-for="officer in options.officers" :key="officer.id" :value="officer.id">
                @{{ officer.name }} <span v-if="officer.position">- @{{ officer.position }}</span>
            </option>
        </select>
        <span class="invalid-feedback d-block" v-if="errors.officer_ids">
            <strong>@{{ errors.officer_ids[0] }}</strong>
        </span>
    </div>

    <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">{{ __('messages.location') }}</h6>
            <button type="button" class="btn btn-sm btn-soft-secondary" @click="showAdvancedLocation = !showAdvancedLocation">
                @{{ showAdvancedLocation ? labels.hide : labels.advanced_location }}
            </button>
        </div>

        <div v-if="!showAdvancedLocation" class="border rounded p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.neighborhood_association') }}</label>
                    <select class="form-select" v-model="form.neighborhood_association_id" @change="loadResidents">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.neighborhoods" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="showAdvancedLocation" class="border rounded p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.country') }}</label>
                    <select class="form-select" v-model="form.country_id" @change="handleCountryChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in options.countries" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.province') }}</label>
                    <select class="form-select" v-model="form.province_id" @change="handleProvinceChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.provinces" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.city') }}</label>
                    <select class="form-select" v-model="form.city_id" @change="handleCityChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.cities" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.district') }}</label>
                    <select class="form-select" v-model="form.district_id" @change="handleDistrictChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.districts" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.village') }}</label>
                    <select class="form-select" v-model="form.village_id" @change="handleVillageChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.villages" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.citizens_associations') }}</label>
                    <select class="form-select" v-model="form.citizens_association_id" @change="handleCitizensChange">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.citizens" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.neighborhood_associations') }}</label>
                    <select class="form-select" v-model="form.neighborhood_association_id" @change="loadResidents">
                        <option value="">{{ __('messages.please_select') }}</option>
                        <option v-for="item in locationOptions.neighborhoods" :key="item.id" :value="item.id">
                            @{{ item.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">{{ __('messages.recipients') }}</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="manualRecipients" v-model="form.use_manual_recipients">
                <label class="form-check-label" for="manualRecipients">{{ __('messages.manual_recipients') }}</label>
            </div>
        </div>

        <div v-if="!form.use_manual_recipients" class="border rounded p-3">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-8">
                    <input type="text" class="form-control" v-model="residentSearch" :placeholder="labels.search">
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-soft-secondary" @click="loadResidents">
                        <i class="ri-search-line align-bottom me-1"></i> {{ __('messages.search') }}
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3" v-if="filteredResidents.length">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllRecipients" v-model="allVisibleSelected">
                    <label class="form-check-label" for="selectAllRecipients">
                        {{ __('messages.select_all') }} (@{{ filteredResidents.length }})
                    </label>
                </div>
                <div class="text-muted small">
                    @{{ form.recipient_ids.length }} {{ __('messages.selected') }}
                </div>
            </div>

            <div v-if="loadingResidents" class="text-muted">{{ __('messages.loading') }}</div>
            <div v-else>
                <div v-if="residents.length === 0" class="text-muted">
                    {{ __('messages.no_residents_found') }}
                </div>
                <div v-else class="row g-2">
                    <div class="col-md-6" v-for="resident in filteredResidents" :key="resident.id">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" :id="'resident-' + resident.id" :value="resident.id" v-model="form.recipient_ids">
                            <label class="form-check-label" :for="'resident-' + resident.id">
                                @{{ resident.name }}
                                <span class="text-muted" v-if="resident.rt || resident.rw">
                                    (RT @{{ resident.rt || '-' }} / RW @{{ resident.rw || '-' }})
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <span class="invalid-feedback d-block" v-if="errors.recipient_ids">
                <strong>@{{ errors.recipient_ids[0] }}</strong>
            </span>
        </div>

        <div v-else class="border rounded p-3">
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-sm btn-soft-primary" @click="addManualRecipient">
                    <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.add_recipient') }}
                </button>
            </div>
            <div v-if="form.manual_recipients.length === 0" class="text-muted">
                {{ __('messages.manual_recipients_hint') }}
            </div>
            <div v-for="(recipient, index) in form.manual_recipients" :key="index" class="border rounded p-3 mb-2">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="recipient.name">
                        <span class="invalid-feedback d-block" v-if="errors[`manual_recipients.${index}.name`]">
                            <strong>@{{ errors[`manual_recipients.${index}.name`][0] }}</strong>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.phone') }}</label>
                        <input type="text" class="form-control" v-model="recipient.phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.address') }}</label>
                        <input type="text" class="form-control" v-model="recipient.address">
                    </div>
                </div>
                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-soft-danger" @click="removeManualRecipient(index)">
                        {{ __('messages.remove') }}
                    </button>
                </div>
            </div>
            <span class="invalid-feedback d-block" v-if="errors.manual_recipients">
                <strong>@{{ errors.manual_recipients[0] }}</strong>
            </span>
        </div>
    </div>

    <div class="mt-4 text-end">
        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ __('messages.save') }}
        </button>
    </div>
</form>
