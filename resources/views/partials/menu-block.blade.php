@use(\Illuminate\Support\Str)
@php
    /** @var string $key */
    $menu = config("menu.$key");
    if (!$menu) return;

    $rootId = 'sidebar-'.Str::slug($menu['title']);
    $rootActive = false;
    foreach (($menu['items'] ?? []) as $node) {
        if (menu_node_active($node)) { $rootActive = true; break; }
    }
@endphp

<li class="menu-title"><span>{{ __($menu['title']) }}</span></li>

<li class="nav-item">
    <a class="nav-link menu-link {{ $rootActive ? 'active' : '' }}"
       href="#{{ $rootId }}" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $rootActive ? 'true' : 'false' }}"
       aria-controls="{{ $rootId }}">
        <i class="{{ $menu['icon'] ?? 'ri-menu-line' }}"></i>
        <span>{{ __($menu['title']) }}</span>
    </a>

    <div class="collapse menu-dropdown {{ $rootActive ? 'show' : '' }}" id="{{ $rootId }}">
        <ul class="nav nav-sm flex-column">
            @foreach (($menu['items'] ?? []) as $node)
                @include('partials.menu-node', ['node' => $node, 'level' => 0])
            @endforeach
        </ul>
    </div>
</li>
