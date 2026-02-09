@csrf

<div class="row g-3 mt-2">
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.context') ?? 'Context' }} <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.context"
                    :class="['form-select', errors.context ? 'is-invalid' : '']">
                <option value="">{{ __('messages.please_select') }}</option>
                <option v-for="(label, value) in options.contexts" :key="value" :value="value">
                    @{{ label }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.context"><strong>@{{ errors.context[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.section') ?? 'Section' }}</label>
            <input type="text" class="form-control" v-model="form.section"
                   :class="['form-control', errors.section ? 'is-invalid' : '']"
                   placeholder="menu.resident.sections.portal">
            <span class="invalid-feedback" v-if="errors.section"><strong>@{{ errors.section[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.label') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.label"
                   :class="['form-control', errors.label ? 'is-invalid' : '']"
                   placeholder="menu.resident.items.dues">
            <span class="invalid-feedback" v-if="errors.label"><strong>@{{ errors.label[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">Icon</label>
            <input type="text" class="form-control" v-model="form.icon"
                   :class="['form-control', errors.icon ? 'is-invalid' : '']"
                   placeholder="ri-dashboard-line">
            <span class="invalid-feedback" v-if="errors.icon"><strong>@{{ errors.icon[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">Route Name</label>
            <input type="text" class="form-control" v-model="form.route_name"
                   :class="['form-control', errors.route_name ? 'is-invalid' : '']"
                   placeholder="dashboard">
            <span class="invalid-feedback" v-if="errors.route_name"><strong>@{{ errors.route_name[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">URL</label>
            <input type="text" class="form-control" v-model="form.url"
                   :class="['form-control', errors.url ? 'is-invalid' : '']" placeholder="/custom">
            <span class="invalid-feedback" v-if="errors.url"><strong>@{{ errors.url[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.organization') }}</label>
            <select class="form-select" v-model="form.organization_id"
                    :class="['form-select', errors.organization_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.none') }}</option>
                <option v-for="organization in options.organizations" :key="organization.id" :value="organization.id">
                    @{{ organization.name }}
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.organization_id">
                <strong>@{{ errors.organization_id[0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.user_level') ?? 'User Level' }}</label>
            <select class="form-select" v-model="form.user_level_id"
                    :class="['form-select', errors.user_level_id ? 'is-invalid' : '']">
                <option value="">{{ __('messages.none') }}</option>
                <option v-for="level in options.levels" :key="level.id" :value="level.id"
                        v-text="level.organization ? level.name + ' (' + level.organization.name + ')' : level.name">
                </option>
            </select>
            <span class="invalid-feedback" v-if="errors.user_level_id">
                <strong>@{{ errors.user_level_id[0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">{{ __('messages.order') }}</label>
            <input type="number" class="form-control" v-model.number="form.order"
                   :class="['form-control', errors.order ? 'is-invalid' : '']" min="0">
            <span class="invalid-feedback" v-if="errors.order"><strong>@{{ errors.order[0] }}</strong></span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Route Parameters (JSON)</label>
            <textarea class="form-control" rows="4" v-model="jsonFields.route_parameters"
                      :class="['form-control', errors.route_parameters ? 'is-invalid' : '']"></textarea>
            <span class="invalid-feedback" v-if="errors.route_parameters">
                <strong>@{{ errors.route_parameters[0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Visibility Rules (JSON)</label>
            <textarea class="form-control" rows="4" v-model="jsonFields.visibility_rules"
                      :class="['form-control', errors.visibility_rules ? 'is-invalid' : '']"></textarea>
            <span class="invalid-feedback" v-if="errors.visibility_rules">
                <strong>@{{ errors.visibility_rules[0] }}</strong>
            </span>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" v-model="form.is_active">
            <label class="form-check-label" for="is_active">{{ __('messages.active') }}</label>
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
