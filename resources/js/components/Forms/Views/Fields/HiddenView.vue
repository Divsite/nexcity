<template>
    <div class="row">
        <div class="col-md-12 hidden-field">
            <div class="mb-3 pe-none">
                <p>{{ field.name }}</p>
                <input type="hidden" v-model="value[field.id]" :id="field.id" class="form-control">
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "HiddenView",
    props: {
        field: {
            type: Object,
            required: false,
        },
        formData: {
            type: Object,
            required: false,
        },
    },
    data() {
        return {
            value: this.formData,
            dataSourceInput: dataSourceInput,
            userInfo: userInfo,
        }
    },
    watch: {
        'field.data_source'(newVal) {
            if (newVal === this.dataSourceInput.text) {
                this.value[this.field.id] = this.field.prefill;
            }

            if (newVal === this.dataSourceInput.current_user) {
                this.value[this.field.id] = this.userInfo[this.field.column_name];
            }
        },
        'field.prefill'(newVal) {
            if (this.field.data_source === this.dataSourceInput.text) {
                this.value[this.field.id] = newVal;
            }
        },
        'field.column_name'() {
            if (this.field.data_source === this.dataSourceInput.current_user) {
                this.value[this.field.id] = this.userInfo[this.field.column_name];
            }
        },
    },
}
</script>

<style>
.hidden-field {
    font-size: 10px;
    background-color: lightgrey !important;
    width: 25%;
    margin: 0 0 10px 15px;
    height: 20px;
}

.hidden-field p {
    margin-top: 3px;
}
</style>

