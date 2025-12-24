<template>
    <div class="modal zoomIn" id="add-field-modal" tabindex="-1" role="dialog"
         aria-labelledby="add-field-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add-field-modal-title">{{ trans.add_field }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="handleClose"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2" v-for="(items, group) in fieldIcons" :key="group">
                        <h6>{{ group }}</h6>
                        <div class="col-xl-4 col-lg-6 mb-3" v-for="(fieldIcon, index) in items" :key="index">
                            <div class="d-grid gap-2">
                                <button class="btn btn-soft-primary" type="button" :disabled="loading" @click="addField(fieldIcon.type)">
                                    <i :class="fieldIcon.icon" class="align-bottom me-1"></i> {{ fieldIcon.name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</template>

<script>
export default {
    name: "FormAddFieldModal",
    props: {
        show: {
            type: Boolean,
            required: false
        }
    },
    data() {
        return {
            field: {
                id: null,
                name: null,
                type: null,
            },
            addFieldModal: null,
            loading: false,
            trans: {
                add_field: trans.add_field,
            },
            typeNames: formTypeNames,
            fieldIcons: formFieldsIcon,
        }
    },
    mounted() {
        this.addFieldModal = new bootstrap.Modal('#add-field-modal', {
            keyboard: false,
            backdrop: 'static',
        });
    },
    watch: {
        show() {
            if (this.show) {
                this.addFieldModal.show();
            }
        }
    },
    methods: {
        reset: function () {
            this.field.name = null;
            this.field.type = null;

            this.value = [];
        },
        addField: async function (field) {
            this.loading = true;

            this.field.name = this.typeNames[field];
            this.field.type = field;

            let data = new FormData();
            data.append('name', this.field.name);
            data.append('type', this.field.type);

            await axios.post(route('form-builder.add-field'), data).then(response => {
                console.log(response);
                this.loading = false;

                if (response.data.status) {
                    let item = {
                        id: response.data.generate_id,
                        name: this.field.name,
                        type: this.field.type,
                        ...response.data.properties,
                    }

                    this.$emit('field-added', item);
                    this.handleClose();
                    this.addFieldModal.hide();
                }
            }).catch((error) => {
                console.log(error.response);

                // If page expired (419)
                if (error.response.status !== 419) {
                    Swal.fire({
                        icon: 'error',
                        title: window.messages.oops,
                        text: error.response.data.message,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                }

                this.loading = false;
            });
        },
        handleClose(){
            this.$emit('close');
            this.reset();
        }
    }
}
</script>
