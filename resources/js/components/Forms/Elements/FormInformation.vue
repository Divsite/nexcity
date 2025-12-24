<template>
    <div class="accordion-item shadow">
        <h2 class="accordion-header" id="information">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#information-collapse" aria-expanded="true"
                    aria-controls="information-collapse">
                <i class="ri-information-line me-2"></i> {{ trans.information }}
            </button>
        </h2>
        <div id="information-collapse" class="accordion-collapse collapse show"
             aria-labelledby="information">
            <div class="accordion-body text-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ trans.form_name }} <span
                                class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" v-model="name"
                                   :class="['form-control', errors.name ? 'is-invalid' : '']" autocomplete="off">
                            <span class="invalid-feedback" v-if="errors.name">
                                <strong>{{ errors.name[0]}}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div :class="['mb-3', errors.type_id ? 'select2-is-invalid' : '']">
                            <label for="name" class="form-label">{{ trans.form_type }}</label>
                            <select id="form-type" class="form-control select2" :data-placeholder="trans.please_select"
                                    v-model="type_id" v-select2>
                                <option v-for="type in formTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <div class="form-text">{{ trans.the_type_specification_of_your_form }}</div>
                            <span class="invalid-feedback d-block" v-if="errors.type_id">
                                <strong>{{ errors.type_id[0] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useFormStore} from "../../../stores/FormStore";
import {select2} from "../../../directives/select2";
import {mapWritableState} from "pinia";

export default {
    name: "FormInformation",
    data() {
        return {
            form: useFormStore(),
            formTypes: formTypes,
            trans: {
                form_name: trans.form_name,
                information: trans.information,
                please_select: trans.please_select,
                form_type: trans.form_type,
                the_type_specification_of_your_form: trans.the_type_specification_of_your_form,
            },
        }
    },
    directives: {select2},
    mounted() {
        $(".select2").select2({
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });
    },
    computed: {
        ...mapWritableState(useFormStore, ['name', 'type_id', 'errors']),
    },
}
</script>
