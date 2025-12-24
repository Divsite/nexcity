<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
    @this.on('triggerDelete', id => {
        Swal.fire({
            title: "{!! __('messages.are_you_sure') !!}",
            text: "{!! __('messages.you_wont_be_able_to_revert_this') !!}",
            icon: "warning",
            showCancelButton: true,
            customClass: {
                confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                cancelButton: 'btn btn-danger w-xs mt-2',
            },
            confirmButtonText: "{!! __('messages.yes_delete_it') !!}",
            cancelButtonText: "{!! __('messages.cancel') !!}",
            buttonsStyling: false,
            showCloseButton: true
        }).then(function (result) {
            if (result.value) {
                @this.call('destroy', id)
            }
        });
    });

    @this.on('triggerRead', id => {
        Swal.fire({
            title: "{!! __('messages.are_you_sure') !!}",
            text: "{!! __('messages.you_wont_be_able_to_revert_this') !!}",
            icon: "warning",
            showCancelButton: true,
            customClass: {
                confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                cancelButton: 'btn btn-danger w-xs mt-2',
            },
            confirmButtonText: "{!! __('messages.yes_mark_as_read') !!}",
            cancelButtonText: "{!! __('messages.cancel') !!}",
            buttonsStyling: false,
            showCloseButton: true
        }).then(function (result) {
            if (result.value) {
            @this.call('setAsHasRead', id)
            }
        });
    });
    })
</script>
