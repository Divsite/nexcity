<template>
    <div class="accordion-item shadow">
        <h2 class="accordion-header" id="integration">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#integration-collapse" aria-expanded="true"
                    aria-controls="integration-collapse">
                <i class="ri-tools-line me-2"></i> {{ trans.integration }}
            </button>
        </h2>
        <div id="integration-collapse" class="accordion-collapse collapse show"
             aria-labelledby="integration">
            <div class="accordion-body text-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" id="use_current_url" v-model="use_current_url"
                                   :value="use_current_url"
                                   :class="['form-check-input', errors.use_current_url ? 'is-invalid' : '']">
                            <label for="use_current_url" class="form-check-label">
                                <div class="d-flex align-items-center">
                                    <span>{{ trans.use_current_url }}</span>
                                </div>
                            </label>
                            <div class="invalid-feedback" v-if="errors.use_current_url">
                                <strong>{{ errors.use_current_url[0] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="webhook_url" class="form-label">
                                        {{ trans.webhook_url }}
                                        <span class="text-muted fs-12" v-if="use_current_url">({{ appUrl }})</span>
                                    </label>
                                    <input type="text" id="webhook_url" v-model="webhook_url"
                                           :class="['form-control', errors.webhook_url ? 'is-invalid' : '']"
                                           autocomplete="off">
                                    <div class="form-text">{{ trans.we_will_post_form_submissions_to_this_url }}</div>
                                    <span class="invalid-feedback" v-if="errors.webhook_url">
                                        <strong>{{ errors.webhook_url[0] }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {useFormStore} from "../../../stores/FormStore";

export default {
    name: "FormIntegration",
    data() {
        return {
            appUrl: appUrl,
            trans: {
                integration: trans.integration,
                webhook_url: trans.webhook_url,
                we_will_post_form_submissions_to_this_url: trans.we_will_post_form_submissions_to_this_url,
                use_current_url: trans.use_current_url,
            },
        }
    },
    computed: {
        ...mapWritableState(useFormStore, ['webhook_url', 'use_current_url', 'errors']),
    },
}
</script>
