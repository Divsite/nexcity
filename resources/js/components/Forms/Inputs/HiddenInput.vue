<template>
    <input type="hidden" v-model="value[field.id]" :id="field.id" class="form-control">
</template>

<script>
export default {
    name: "HiddenInput",
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
            id: this.field.id,
            value: this.formData,
        }
    },
}
</script>


