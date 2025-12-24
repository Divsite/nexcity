<div class="list-group list-group-flush">
    @can('my-account')
        <a href="{{ route(config('core.profile_route_name')) }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active(config('core.profile_route_name')) }}"><i
                class="ri-account-circle-fill align-middle me-2"></i>{{ __('messages.my_account') }}</a>
    @endcan
    @can('change-password')
        <a href="{{ route('profile.change-password-form') }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active('profile.change-password-form') }}"><i
                class="ri-lock-password-fill align-middle me-2"></i>{{ __('messages.change_password') }}</a>
    @endcan
    @can('change-username')
        <a href="{{ route('profile.change-username-form') }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active('profile.change-username-form') }}"><i
                class="ri-admin-fill align-middle me-2"></i>{{ __('messages.change_username') }}</a>
    @endcan
    @can('change-email')
        <a href="{{ route('profile.change-email-form') }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active('profile.change-email-form') }}"><i
                class="ri-mail-fill align-middle me-2"></i>{{ __('messages.change_email') }}</a>
    @endcan
    @can('my-activities')
        <a href="{{ route('profile.activities') }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active('profile.activities') }}"><i
                class="ri-history-fill align-middle me-2"></i>{{ __('messages.my_activities') }}</a>
    @endcan
    @can('recent-sessions')
        <a href="{{ route('profile.recent-sessions') }}"
           class="list-group-item list-group-item-action border-bottom rounded {{ set_active('profile.recent-sessions') }}"><i
                class="ri-login-circle-fill align-middle me-2"></i>{{ __('messages.recent_sessions') }}</a>
    @endcan
</div>
