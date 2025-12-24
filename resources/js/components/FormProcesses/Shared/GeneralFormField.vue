<template>
    <div class="row">
        <div class="col-lg-12">
            <div class="mb-3">
                <label for="name" class="form-label">
                    {{ trans.name }} <span class="text-danger">*</span>
                </label>
                <input type="text" id="name" v-model="form.name"
                       :class="['form-control', errors.name ? 'is-invalid' : '']"
                       @keydown.enter.prevent>
                <div class="invalid-feedback" v-if="errors.name">
                    <strong>{{ errors.name[0] }}</strong>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="mb-3">
                <label for="status_enabled" class="form-label">
                    {{ trans.status }}
                </label>
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.status ? 'is-invalid' : '']"
                           :id="toggle_enabled" v-model="form.status">
                    <label :for="toggle_enabled" class="form-check-label">
                        <div class="d-flex align-items-center">
                            <span>{{ trans.enabled }}</span>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.status">
                    <strong>{{ errors.status[0] }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "GeneralFormField",
    props: ['trans', 'form', 'errors', 'toggle_enabled'],
}
</script>
