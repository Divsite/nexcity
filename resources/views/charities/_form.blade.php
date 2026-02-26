<form @submit.prevent="submitForm">
    <div class="row g-3">
        <div class="col-lg-6">
            <div :class="['mb-0', errors.charity_type_id ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.charity_type') }} <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.charity_type_id">
                    <option value="">{{ __('messages.please_select') }}</option>
                    <option v-for="item in options.charity_types" :key="item.id" :value="item.id">
                        @{{ item.name || '-' }} (@{{ item.year }})
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.charity_type_id">
                    <strong>@{{ errors.charity_type_id[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-3">
            <div :class="['mb-0', errors.year ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.year') }} <span class="text-danger">*</span></label>
                <input type="number" class="form-control" v-model="form.year" min="2000" max="2100" readonly>
                <span class="invalid-feedback d-block" v-if="errors.year">
                    <strong>@{{ errors.year[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-3" v-if="mode === 'edit'">
            <div :class="['mb-0', errors.status ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.status">
                    <option v-for="status in options.statuses" :value="status.value" :key="status.value">
                        @{{ status.label }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.status">
                    <strong>@{{ errors.status[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3" v-if="selectedCharityType">
        <div class="fw-semibold mb-1">{{ __('messages.charity_type_rules') }}</div>
        <div class="small text-muted mb-1">
            @{{ selectedCharityType.source ? selectedCharityType.source.name : '-' }}
        </div>
        <ul class="mb-0 ps-3">
            <li>{{ __('messages.min_amount') }}: <strong>@{{ formatCurrency(selectedCharityType.min_amount) }}</strong></li>
            <li>{{ __('messages.max_amount') }}: <strong>@{{ formatCurrency(selectedCharityType.max_amount) }}</strong></li>
            <li>
                {{ __('messages.is_rice') }}:
                <strong v-if="selectedCharityType.is_rice">{{ __('messages.yes') }}</strong>
                <strong v-else>{{ __('messages.no') }}</strong>
            </li>
            <li v-if="selectedCharityType.is_rice">{{ __('messages.total_rice') }}: <strong>@{{ selectedCharityType.total_rice || 0 }} {{ __('messages.liter') }}</strong></li>
        </ul>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <h6 class="mb-0">{{ __('messages.step_payer_identity') }}</h6>
        <span class="badge" :class="form.is_package ? 'bg-soft-primary text-primary' : 'bg-soft-success text-success'">
            <span v-if="form.is_package">{{ __('messages.family_mode') }}</span>
            <span v-else>{{ __('messages.individual_mode') }}</span>
        </span>
    </div>

    <div class="row g-3 mt-1" v-if="!form.is_package">
        <div class="col-lg-4">
            <div :class="['mb-0', errors.payer_name ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.payer_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.payer_name">
                <span class="invalid-feedback d-block" v-if="errors.payer_name">
                    <strong>@{{ errors.payer_name[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-4">
            <div :class="['mb-0', errors.payer_phone ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.payer_phone') }}</label>
                <input type="text" class="form-control" v-model="form.payer_phone">
                <span class="invalid-feedback d-block" v-if="errors.payer_phone">
                    <strong>@{{ errors.payer_phone[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-4">
            <div :class="['mb-0', errors.payer_email ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.payer_email') }}</label>
                <input type="email" class="form-control" v-model="form.payer_email">
                <span class="invalid-feedback d-block" v-if="errors.payer_email">
                    <strong>@{{ errors.payer_email[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <template v-else>
        <div class="alert alert-secondary mt-3 mb-0 py-2">
            {{ __('messages.family_package_mode_enabled') }} {{ __('messages.family_count_includes_representative') }}
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <div :class="['mb-0', errors.payer_name ? 'is-invalid' : '']">
                    <label class="form-label">{{ __('messages.representative_payer_name') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="form.payer_name">
                    <span class="invalid-feedback d-block" v-if="errors.payer_name">
                        <strong>@{{ errors.payer_name[0] }}</strong>
                    </span>
                </div>
            </div>
            <div class="col-lg-4">
                <div :class="['mb-0', errors.payer_email ? 'is-invalid' : '']">
                    <label class="form-label">{{ __('messages.payer_email') }}</label>
                    <input type="email" class="form-control" v-model="form.payer_email">
                    <span class="invalid-feedback d-block" v-if="errors.payer_email">
                        <strong>@{{ errors.payer_email[0] }}</strong>
                    </span>
                </div>
            </div>
            <div class="col-lg-4">
                <div :class="['mb-0', errors.payer_phone ? 'is-invalid' : '']">
                    <label class="form-label">{{ __('messages.payer_phone') }}</label>
                    <input type="text" class="form-control" v-model="form.payer_phone">
                    <span class="invalid-feedback d-block" v-if="errors.payer_phone">
                        <strong>@{{ errors.payer_phone[0] }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </template>

    <hr class="my-3">
    <h6 class="mb-2">{{ __('messages.step_payment_details') }}</h6>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div :class="['mb-0', errors.payment_method ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.payment_method') }} <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.payment_method">
                    <option v-for="method in options.payment_methods" :value="method.value" :key="method.value">
                        @{{ method.label }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.payment_method">
                    <strong>@{{ errors.payment_method[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-4" v-if="form.payment_method === 'transfer' || form.payment_method === 'qris'">
            <div :class="['mb-0', errors.charity_payment_id ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.charity_payments') }} <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.charity_payment_id">
                    <option value="">{{ __('messages.please_select') }}</option>
                    <option v-for="payment in options.payments" :value="payment.id" :key="payment.id">
                        @{{ payment.type ? payment.type.toUpperCase() : '-' }} - @{{ payment.bank_name || '-' }} - @{{ payment.account_name || '-' }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.charity_payment_id">
                    <strong>@{{ errors.charity_payment_id[0] }}</strong>
                </span>

                <div class="alert alert-light border mt-2 mb-0 py-2" v-if="selectedPayment">
                    <div class="small text-muted mb-1">{{ __('messages.payment_transfer_target') }}</div>
                    <div class="fw-semibold">@{{ selectedPayment.bank_name || '-' }}</div>
                    <div class="small">{{ __('messages.account_name') }}: <strong>@{{ selectedPayment.account_name || '-' }}</strong></div>
                    <div class="small">{{ __('messages.account_number') }}: <strong>@{{ selectedPayment.account_number || '-' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div :class="['mb-0', errors.total_money ? 'is-invalid' : '']">
                <label class="form-label">
                    {{ __('messages.total_money') }}
                    <span class="text-danger" v-if="!selectedCharityType || !selectedCharityType.is_rice || !form.detail.is_rice">*</span>
                </label>
                <vue-currency-input
                    v-model="form.total_money"
                    :options="currencyOptions"
                    :class="`form-control ${errors.total_money ? 'is-invalid' : ''}`"
                    :disabled="form.is_package"
                ></vue-currency-input>
                <span class="invalid-feedback d-block" v-if="errors.total_money">
                    <strong>@{{ errors.total_money[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-lg-4" v-if="selectedCharityType && selectedCharityType.is_rice">
            <div class="mb-0">
                <label class="form-label d-block">{{ __('messages.is_rice') }}</label>
                <div class="form-check form-switch form-switch-md">
                    <input class="form-check-input" type="checkbox" id="charity-is-rice" v-model="form.detail.is_rice">
                    <label class="form-check-label" for="charity-is-rice">
                        {{ __('messages.include_rice_payment') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="col-lg-4" v-if="selectedCharityType && selectedCharityType.is_rice && form.detail.is_rice">
            <div :class="['mb-0', errors.total_rice ? 'is-invalid' : '']">
                <label class="form-label">{{ __('messages.total_rice') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" class="form-control" v-model="form.total_rice" min="0" step="0.01">
                    <span class="input-group-text">{{ __('messages.liter') }}</span>
                </div>
                <span class="invalid-feedback d-block" v-if="errors.total_rice">
                    <strong>@{{ errors.total_rice[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" id="charity-is-package" v-model="form.is_package">
        <label class="form-check-label" for="charity-is-package">
            {{ __('messages.is_package') }}
        </label>
    </div>

    <div class="mt-3" v-if="form.is_package">
        <hr class="my-3">
        <h6 class="mb-2">{{ __('messages.step_family_package') }}</h6>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div :class="['mb-0', errors.package_members_count ? 'is-invalid' : '']">
                    <label class="form-label">{{ __('messages.family_members_count') }} <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        min="1"
                        class="form-control"
                        v-model="form.package_members_count"
                        :readonly="!form.use_same_package_amount || form.is_input_family_members"
                    >
                    <span class="invalid-feedback d-block" v-if="errors.package_members_count">
                        <strong>@{{ errors.package_members_count[0] }}</strong>
                    </span>
                    <div class="form-text" v-if="form.use_same_package_amount && form.is_input_family_members">
                        {{ __('messages.family_members_auto_formula') }}: @{{ familyMembersRowsCount }} + 1 = @{{ form.package_members_count || 0 }}
                    </div>
                    <div class="form-text" v-else-if="form.use_same_package_amount && !form.is_input_family_members">
                        {{ __('messages.family_members_manual_hint') }}
                    </div>
                    <div class="form-text" v-else>
                        {{ __('messages.family_members_auto_formula') }}: @{{ familyMembersRowsCount }} + 1 = @{{ form.package_members_count || 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="charity-use-same-package-amount" v-model="form.use_same_package_amount">
            <label class="form-check-label" for="charity-use-same-package-amount">
                {{ __('messages.use_same_package_amount') }}
            </label>
        </div>

        <div class="row mb-3" v-if="form.use_same_package_amount">
            <div class="col-lg-4">
                <label class="form-label">{{ __('messages.package_amount_each') }} <span class="text-danger">*</span></label>
                <vue-currency-input
                    v-model="form.package_amount_each"
                    :options="currencyOptions"
                    :class="`form-control ${errors.package_amount_each ? 'is-invalid' : ''}`"
                ></vue-currency-input>
                <span class="invalid-feedback d-block" v-if="errors.package_amount_each">
                    <strong>@{{ errors.package_amount_each[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="form-check mb-3" v-if="form.use_same_package_amount">
            <input class="form-check-input" type="checkbox" id="charity-is-input-family-members" v-model="form.is_input_family_members">
            <label class="form-check-label" for="charity-is-input-family-members">
                {{ __('messages.input_family_members_data') }}
            </label>
        </div>

        <div class="row mb-3" v-if="!form.use_same_package_amount">
            <div class="col-lg-4">
                <label class="form-label">{{ __('messages.representative_total_money') }} <span class="text-danger">*</span></label>
                <vue-currency-input
                    v-model="form.representative_total_money"
                    :options="currencyOptions"
                    :class="`form-control ${errors.representative_total_money ? 'is-invalid' : ''}`"
                ></vue-currency-input>
                <span class="invalid-feedback d-block" v-if="errors.representative_total_money">
                    <strong>@{{ errors.representative_total_money[0] }}</strong>
                </span>
                <div class="form-text">{{ __('messages.representative_amount_hint') }}</div>
            </div>
        </div>

        <div class="alert alert-light border mb-3" v-if="form.use_same_package_amount">
            {{ __('messages.package_payers_optional_note') }}
        </div>

        <template v-if="!form.use_same_package_amount || form.is_input_family_members">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">{{ __('messages.package_payers') }}</h6>
                <button type="button" class="btn btn-sm btn-soft-primary" @click="addPackagePayer">
                    <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.add') }}
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.name') }} <span class="text-danger">*</span></th>
                        <th>{{ __('messages.phone') }}</th>
                        <th>{{ __('messages.email') }}</th>
                        <th>{{ __('messages.total_money') }} <span class="text-danger" v-if="!form.use_same_package_amount">*</span></th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(payer, index) in form.package_payers" :key="`payer-${index}`">
                        <td>
                            <input type="text" class="form-control"
                                   :class="firstError('package_payers.' + index + '.payer_name') ? 'is-invalid' : ''"
                                   v-model="payer.payer_name">
                            <span class="invalid-feedback d-block" v-if="firstError('package_payers.' + index + '.payer_name')">
                                <strong>@{{ firstError('package_payers.' + index + '.payer_name') }}</strong>
                            </span>
                        </td>
                        <td>
                            <input type="text" class="form-control" v-model="payer.payer_phone">
                        </td>
                        <td>
                            <input type="email" class="form-control"
                                   :class="firstError('package_payers.' + index + '.payer_email') ? 'is-invalid' : ''"
                                   v-model="payer.payer_email">
                            <span class="invalid-feedback d-block" v-if="firstError('package_payers.' + index + '.payer_email')">
                                <strong>@{{ firstError('package_payers.' + index + '.payer_email') }}</strong>
                            </span>
                        </td>
                        <td>
                            <vue-currency-input
                                v-model="payer.total_money"
                                :options="currencyOptions"
                                :disabled="form.use_same_package_amount"
                                :class="`form-control ${firstError('package_payers.' + index + '.total_money') ? 'is-invalid' : ''}`"
                            ></vue-currency-input>
                            <span class="invalid-feedback d-block" v-if="firstError('package_payers.' + index + '.total_money')">
                                <strong>@{{ firstError('package_payers.' + index + '.total_money') }}</strong>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-soft-danger" @click="removePackagePayer(index)">
                                {{ __('messages.delete') }}
                            </button>
                        </td>
                    </tr>

                    <tr v-if="form.package_payers.length === 0">
                        <td colspan="5" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <span class="invalid-feedback d-block" v-if="errors.package_payers">
                <strong>@{{ errors.package_payers[0] }}</strong>
            </span>
        </template>

        <div class="alert alert-warning py-2 mb-0" v-else>
            {{ __('messages.package_payers_optional_note') }}
        </div>

        <div class="alert alert-info py-2 mt-3 mb-0">
            <span class="fw-semibold">{{ __('messages.total_preview') }}:</span>
            @{{ formatCurrency(form.total_money) }}
        </div>
    </div>

    <div class="mt-3">
        <label class="form-label">{{ __('messages.notes') }}</label>
        <textarea class="form-control" rows="3" v-model="form.notes"></textarea>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            <span v-if="mode === 'edit'">{{ __('messages.save') }}</span>
            <span v-else>{{ __('messages.create') }}</span>
        </button>
    </div>
</form>
