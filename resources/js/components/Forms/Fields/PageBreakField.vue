<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="next_btn_text" class="form-label">{{ trans.text_of_next_button }}</label>
                <input type="text" id="next_btn_text" v-model="value.next_btn_text"
                       :class="['form-control', errors.next_btn_text ? 'is-invalid' : '']">
                <div class="invalid-feedback d-block mb-3" v-if="errors.next_btn_text">
                    <strong>{{ errors.next_btn_text[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="previous_btn_text" class="form-label">{{ trans.text_of_previous_button }}</label>
                <input type="text" id="previous_btn_text" v-model="value.previous_btn_text"
                       :class="['form-control', errors.previous_btn_text ? 'is-invalid' : '']">
                <div class="invalid-feedback d-block mb-3" v-if="errors.previous_btn_text">
                    <strong>{{ errors.previous_btn_text[0] }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PageBreakField",
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
    data() {
        return {
            value: {
                next_btn_text: this.form.next_btn_text,
                previous_btn_text: this.form.previous_btn_text,
            },
            fieldWidthList: fieldWidth,
            trans: {
                text_of_next_button: trans.text_of_next_button,
                text_of_previous_button: trans.text_of_previous_button,
            }
        }
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
