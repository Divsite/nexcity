import "../../app";

import { createApp } from "vue";

createApp({
    name: 'ReadConfirmation',
    data() {
        return {
            read_button_key: 0,
            loading: false,
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    methods: {
        triggerRead: async function (url) {
            this.loading = true;

            await Swal.fire({
                title: window.messages.are_you_sure,
                text: window.messages.you_wont_be_able_to_revert_this,
                icon: "warning",
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                    cancelButton: 'btn btn-danger w-xs mt-2',
                },
                confirmButtonText: window.messages.yes_mark_as_read,
                cancelButtonText: window.messages.cancel,
                buttonsStyling: false,
                showCloseButton: true
            }).then(async function (result) {
                if (result.value) {
                    await axios.get(url).then(response => {
                        if (response.data.success) {
                            window.location.href = response.data.redirect;
                        }
                    }).catch((error) => {
                        console.log(error);
                    });
                }
            });

            this.loading = false; // Stop loading
            this.read_button_key++;
        },
    }
}).mount('#read-confirmation')
