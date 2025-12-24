<!-- Notification -->
<div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle shadow-none"
            id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
        <i class="bx bx-bell fs-22"></i>
        @if(auth()->user()->unreadNotifications->count() != 0)
            <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">
            {{ auth()->user()->unreadNotifications->count() }}
            <span class="visually-hidden">
                unread messages
            </span>
        </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
         aria-labelledby="page-header-notifications-dropdown">
        <div class="dropdown-head bg-primary bg-pattern rounded-top">
            <div class="p-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="m-0 fs-16 fw-semibold text-white"> {{ __('messages.notifications') }} </h6>
                    </div>
                    <div class="col-auto dropdown-tabs">
                        <span class="badge bg-light-subtle text-body fs-13">
                            {{ auth()->user()->unreadNotifications->count() }} {{ __('messages.new') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div data-simplebar style="max-height: 300px;" class="pe-2">
            @if(auth()->user()->unreadNotifications->count() != 0)
                <div class="my-3 text-center">
                    <a href="{{ route('notifications.mark-as-read') }}"
                       class="btn btn-soft-danger btn-sm waves-effect waves-light">
                        {{ __('messages.mark_all_as_read') }} <i class="ri-check-double-line align-middle"></i>
                    </a>
                </div>
            @endif
            <div class="py-2 ps-2">
                @forelse (auth()->user()->unreadNotifications as $notification)
                    @if(!empty($notification->data['redirect_to']))
                    <a href="{{ $notification->data['redirect_to'] }}">
                    @else
                    <a href="{{ route('notifications.show', $notification->id) }}">
                    @endif
                        <div class="text-reset notification-item d-block dropdown-item position-relative">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <h6 class="mt-0 mb-2 lh-base">{{ $notification->data['data'] ?? null }}</h6>
                                    <p class="mb-0 fs-11 fw-medium text-uppercase text-muted text-right">
                                        <span><i class="mdi mdi-clock-outline"></i> {{ $notification->created_at->diffForHumans() }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center mt-3">
                        <h6 class="fs-14 fw-semibold lh-base">{{ __('messages.no_unread_notification') }}</h6>
                    </div>
                @endforelse
            </div>
            <div class="my-3 text-center view-all">
                <a href="{{ route('notifications.index') }}" class="btn btn-soft-success waves-effect waves-light">
                    {{ __('messages.view_all_notifications') }} <i class="ri-arrow-right-line align-middle"></i>
                </a>
            </div>
        </div>
    </div>
</div>
