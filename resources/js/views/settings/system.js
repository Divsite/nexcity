import {createApp} from "vue";
import vueFilePond from "vue-filepond";

// Import plugins
import FilePondPluginFileValidateType
    from 'filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.esm.js';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.esm.js';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';

// Import styles
import '../../../../public/assets/libs/filepond/filepond.min.css';
import '../../../../public/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css';

// Create FilePond component
const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview, FilePondPluginFileValidateSize);

createApp({
    name: 'SystemSettingForm',
    components: {
        FilePond
    },
    data() {
        return {
            settings: window.settings,
            logo_sm: null,
            logo_lg: null,
            favicon: null,
            logoFileTypes: ['image/jpeg', 'image/png'],
            faviconFileTypes: ['image/jpeg', 'image/png', 'image/x-icon'],
            submitFormKey: 0,
            errors: [],
            loading: false,
        }
    },
    methods: {
        handleFilePondInit: function () {
            if (this.$refs.pond.current) {
                this.$refs.pond.current.getFiles();
            }
        },
        handleFileUpdateForLogoSm: function (error, fileItem) {
            if (!error) {
                this.logo_sm = fileItem.file ?? null;
            }
        },
        handleFileUpdateForLogoLg: function (error, fileItem) {
            if (!error) {
                this.logo_lg = fileItem.file ?? null;
            }
        },
        handleFileUpdateForFavicon: function (error, fileItem) {
            if (!error) {
                this.favicon = fileItem.file ?? null;
            }
        },
        isFile: function (file) {
            // Check if the object is an instance of File
            return file instanceof File;
        },
        submitForm: async function (loading = true) {
            this.loading = loading;

            let data = new FormData();
            data.append('name', this.settings.name);

            if (this.isFile(this.logo_sm)) {
                data.append('logo_sm', this.logo_sm);
            }

            if (this.isFile(this.logo_lg)) {
                data.append('logo_lg', this.logo_lg);
            }

            if (this.isFile(this.favicon)) {
                data.append('favicon', this.favicon);
            }

            await axios.post(route('settings.system.store'), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submitFormKey++;
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app')
