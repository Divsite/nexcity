<div class="d-flex gap-2 align-items-center">
    <div class="flex-shrink-0">
        <img src="{{ asset(\App\Models\Users\User::AVATAR_PATH . $row->avatar) }}" alt=""
             class="avatar-xs rounded-circle"/></div>
    <div class="flex-grow-1">{{ $row->name }}</div>
</div>
