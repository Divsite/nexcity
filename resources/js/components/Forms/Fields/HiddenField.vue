<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-label fw-medium">{{ trans.data_source }} <span class="text-danger">*</span></div>
                <div class="btn-group" role="group" aria-label="Data source">
                    <template v-for="(input, index) in dataSourceInputNames[this.formStore.data.formFields.hidden]">
                        <input type="radio" class="btn-check" :id="index" v-model="value.data_source" :value="index"
                               autocomplete="off">
                        <label class="btn btn-outline-primary" :for="index">{{ input }}</label>
                    </template>
                </div>
                <div class="invalid-feedback d-block" v-if="errors.data_source">
                    <strong>{{ errors.data_source[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row" v-if="value.data_source === dataSourceInput.text">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="prefill" class="form-label">{{ trans.prefill_value }}</label>
                <input type="text" id="prefill" v-model="value.prefill"
                       :class="['form-control', errors.prefill ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.prefill">
                    <strong>{{ errors.prefill[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row" v-show="value.data_source === dataSourceInput.current_user">
        <div class="col-md-12">
            <div :class="['mb-3', errors.column_name ? 'select2-is-invalid' : '']">
                <label for="column_name" class="form-label">
                    {{ trans.column_name }}
                    <span class="text-danger">*</span>
                </label>
                <select id="column_name" class="form-control select2" :data-placeholder="trans.please_select"
                        v-model="value.column_name" v-select2>
                    <option v-for="(value, name) in userColumnNames" :key="name" :value="name">
                        {{ value }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.column_name">
                    <strong>{{ errors.column_name[0] }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import {select2} from "../../../directives/select2";

export default {
    name: "HiddenField",
    emits: ['value-updated'],
    props: {
        form: {
            type: Object,
            required: false,
        },
        errors: {
            type: Object,
            required: false,
        },
    },
    directives: {select2},
    data() {
        return {
            value: {
                data_source: this.form.data_source,
                prefill: this.form.prefill,
                column_name: this.form.column_name,
            },
            dataSourceInput: dataSourceInput,
            dataSourceInputNames: dataSourceInputNames,
            userColumnNames: userColumnNames,
            trans: {
                prefill_value: trans.prefill_value,
                data_source: trans.data_source,
                column_name: trans.column_name,
                please_select: trans.please_select,
            }
        }
    },
    mounted() {
        $(".select2").select2({
            dropdownParent: $('#edit-field-modal'),
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });
    },
    watch: {
        value: {
            handler() {
                this.$emit('value-updated', this.value);
            },
            deep: true
        }
    },
}
</script>
