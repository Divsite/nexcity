@use(\Illuminate\Support\Str)
@php
    if (isset($node['can']) && !menu_can_show($node['can'])) { return; }

    $hasChildren = !empty($node['children'] ?? []);
    $icon = $node['icon'] ?? 'ri-folder-2-line';
    $title = __($node['title'] ?? 'Menu');
    $active = menu_node_active($node);
    $collapseId = 'menu-'.Str::slug($title).'-'.$level;
@endphp

@if ($hasChildren)
    <li class="nav-item">
        <a class="nav-link {{ $active ? 'active' : '' }}" href="#{{ $collapseId }}"
           data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $active ? 'true' : 'false' }}"
           aria-controls="{{ $collapseId }}">
            <i class="{{ $icon }}"></i> <span>{{ Str::title($title) }}</span>
        </a>
        <div class="collapse menu-dropdown {{ $active ? 'show' : '' }}" id="{{ $collapseId }}">
            <ul class="nav nav-sm flex-column">
                @foreach($node['children'] as $child)
                    @include('partials.menu-node', ['node' => $child, 'level' => $level + 1])
                @endforeach
            </ul>
        </div>
    </li>
@else
    <li class="nav-item">
        @if (!empty($node['route']) && Route::has($node['route']))
            <a href="{{ route($node['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
                @if (!empty($icon)) <i class="{{ $icon }}"></i> @endif
                <span>{{ Str::title($title) }}</span>
            </a>
        @else
            <a href="#" class="nav-link disabled">
                @if (!empty($icon)) <i class="{{ $icon }}"></i> @endif
                <span>{{ Str::title($title) }}</span>
            </a>
        @endif
    </li>
@endif
