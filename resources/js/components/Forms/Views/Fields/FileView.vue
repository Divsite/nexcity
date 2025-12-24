<template>
    <div :class="field.width">
        <div class="mb-3">
            <div :id="field.id" class="form-label fw-medium">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </div>
            <file-pond
                ref="pond"
                :label-file-processing="trans.uploading"
                :label-file-processing-complete="trans.upload_complete"
                :label-file-processing-aborted="trans.upload_cancelled"
                :label-file-processing-error="trans.error_during_upload"
                :label-file-remove-error="trans.error_during_remove"
                :label-tap-to-cancel="trans.tap_to_cancel"
                :label-tap-to-retry="trans.tap_to_retry"
                :label-tap-to-undo="trans.tap_to_undo"
                :required="requiredField()"
                :disabled="disableField()"
                :allow-file-type-validation="allowFileTypeValidation"
                :accepted-file-types="acceptFile"
                :label-file-type-not-allowed="trans.file_of_invalid_type"
                :file-validate-type-label-expected-types="trans.expects"
                :allow-multiple="field.multiple_files"
                :label-idle="trans.drag_and_drop_your_files_or_browse"
                :max-files="maxFiles"
                :max-file-size="maxFileSize"
                :label-max-file-size-exceeded="trans.file_is_too_large"
                :label-max-file-size="trans.maximum_file_size_is"
                :server="server"
                v-on:init="handleFilePondInit"
            />
            <div class="form-text" v-if="field.help">{{ field.help }}</div>
        </div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";

// Import FilePond
import vueFilePond from 'vue-filepond';

// Import plugins
import FilePondPluginFileValidateType
    from 'filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.esm.js';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.esm.js';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';

// Import styles
import '../../../../../../public/assets/libs/filepond/filepond.min.css';
import '../../../../../../public/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css';

// Create FilePond component
const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview, FilePondPluginFileValidateSize);

export default {
    name: "FileView",
    components: {
        FilePond,
    },
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
            actionTypes: actionTypes,
            server: {
                process: {
                    url: route('form-submission-files.preview.store'),
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken,
                    },
                    onload: (response) => {
                        let data = JSON.parse(response);
                        this.value[this.field.id].push(data.folder);
                        return data.folder;
                    }
                },
                revert: {
                    url: route('form-submission-files.preview.destroy'),
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.Laravel.csrfToken,
                    },
                    onload: (response) => {
                        let data = JSON.parse(response);
                        let folder = data.folder;

                        let files = collect(this.value[this.field.id]);

                        let filtered = files.reject(function(item) {
                            return item === folder;
                        });

                        this.value[this.field.id] = filtered.all();
                    },
                    ondata: (formData) => {
                        formData.append('_method', 'DELETE');
                        return formData;
                    },
                },
            },
            trans: {
                drag_and_drop_your_files_or_browse: trans.drag_and_drop_your_files_or_browse,
                file_of_invalid_type: trans.file_of_invalid_type,
                expects: trans.expects,
                file_is_too_large: trans.file_is_too_large,
                maximum_file_size_is: trans.maximum_file_size_is,
                uploading: trans.uploading,
                upload_complete: trans.upload_complete,
                upload_cancelled: trans.upload_cancelled,
                error_during_upload: trans.error_during_upload,
                error_during_remove: trans.error_during_remove,
                tap_to_cancel: trans.tap_to_cancel,
                tap_to_retry: trans.tap_to_retry,
                tap_to_undo: trans.tap_to_undo,
            }
        }
    },
    computed: {
        allowFileTypeValidation() {
            return this.acceptFile.length > 0;
        },
        acceptFile() {
            if (this.field.accept_files !== null) {
                return this.field.accept_files.toLowerCase().split(" ").join("").split(',');
            }

            return [];
        },
        maxFiles() {
            return this.field.multiple_files ? this.field.max_files : 1;
        },
        maxFileSize() {
            return this.field.max_file_size + 'MB';
        }
    },
    watch: {
        field: {
            handler() {
                // Reset file if field has update
                this.$refs.pond.removeFiles();
            },
            deep: true
        },
    },
    methods: {
        handleFilePondInit: function () {
            console.log('FilePond has initialized');

            // example of instance method call on pond reference
            this.$refs.pond.getFiles();
        },
        requiredField: function () {
            let val = this.field.required;

            if (this.field.logic.enabled) {
                if (conditionMet(this.field, this.value)) {
                    if (this.field.logic.actions && this.field.logic.actions.length > 0) {
                        let actions = collect(this.field.logic.actions);

                        actions.each((action) => {
                            if (action.type === this.actionTypes.require) {
                                val = true;
                            }

                            if (action.type === this.actionTypes.optional) {
                                val = false;
                            }
                        });
                    }
                }
            }

            return val;
        },
        disableField: function () {
            let val = this.field.disabled;

            if (this.field.logic.enabled) {
                if (conditionMet(this.field, this.value)) {
                    if (this.field.logic.actions && this.field.logic.actions.length > 0) {
                        let actions = collect(this.field.logic.actions);

                        actions.each((action) => {
                            if (action.type === this.actionTypes.disable) {
                                val = true;
                            }

                            if (action.type === this.actionTypes.enable) {
                                val = false;
                            }
                        });
                    }
                }
            }

            return val;
        }
    }
}
</script>

<style>
.filepond--root {
    margin-bottom: 0;
}
.filepond--root[data-style-panel-layout~=circle] .filepond--drop-label label {
    font-size: 14px;
}

.filepond--panel-root {
    border: 2px dashed var(--vz-border-color);
    background: var(--vz-secondary-bg);
}

.filepond--drop-label {
    color: var(--vz-body-color);
}
.filepond--drop-label label {
    font-weight: 500;
}

.filepond--credits {
    display: none;
}

.filepond--item-panel {
    background-color: #405189 !important;
}
</style>
