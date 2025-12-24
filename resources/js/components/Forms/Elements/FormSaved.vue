<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h4>{{ form.name }}</h4>
                </div>
                <div class="col-md-12">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-load" type="button" :disabled="loading" @click.prevent="submitForm()" :key="submitFormKey">
                        <span class="d-flex justify-content-center">
                            <span class="spinner-border" role="status" v-if="loading">
                                <span class="visually-hidden">{{ trans.loading }}</span>
                            </span>
                            <span class="fs-14" :class="[loading ? 'ms-2' : '']">
                                <i class="ri-save-3-line align-bottom me-1"></i> {{ trans.save_changes }}
                            </span>
                        </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useFormStore} from "../../../stores/FormStore";

export default {
    name: "FormSaved",
    data() {
        return {
            form: useFormStore(),
            loading: false,
            submitFormKey: 0,
            trans: {
                save_changes: trans.save_changes,
            }
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let values = {
                name: this.form.name,
                type_id: this.form.type_id,
                properties: this.form.properties,
                webhook_url: this.form.webhook_url,
                use_current_url: this.form.use_current_url,
            };

            let data = JSON.parse(JSON.stringify(values));

            let method = 'POST';
            let url = route('forms.store');
            if (this.form.id) {
                method = 'PUT';
                url = route('forms.update', {form: this.form.id})
            }
            data._method = method;

            await axios.post(url, data).then(response => {
                console.log(response);
                this.form.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.form.errors = error.response.data.errors;
                }
                this.submitFormKey++;
                this.loading = false; // Stop loading
            });
        }
    }
}
</script>
