<!-- JAVASCRIPT -->
<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
{{--<script src="{{ asset('assets/js/plugins.js') }}"></script>--}}

<script>
    window.Laravel = {csrfToken: '{{ csrf_token() }}'}
    window.default_locale = "{{ config('app.locale') }}";
    window.fallback_locale = "{{ config('app.fallback_locale') }}";
    window.theme = "{{ $theme['color-mode'] }}";

    // global translation (sent to js)
    // for specific page, better use translation on the file
    window.messages = {
        'are_you_sure': '{{ __('messages.are_you_sure') }}',
        'cancel': '{{ __('messages.cancel') }}',
        'oops': '{{ __('messages.oops') }}',
        'page_expired_try_again': '{{ __('messages.page_expired_try_again') }}',
        'yes_delete_it': '{{ __('messages.yes_delete_it') }}',
        'yes_mark_as_read': '{{ __('messages.yes_mark_as_read') }}',
        'you_wont_be_able_to_revert_this': "{!! __('messages.you_wont_be_able_to_revert_this') !!}",
        'no_results_found': "{{ __('messages.no_results_found') }}",
    };
</script>

<!-- scripts -->
@vite('resources/js/app.js')

<!-- Livewire Scripts -->
@livewireScriptConfig
@routes
@include('sweetalert::alert', ['cdn' => "/assets/libs/sweetalert2/sweetalert2.min.js"])

@stack('vendor-scripts')

@stack('scripts')
