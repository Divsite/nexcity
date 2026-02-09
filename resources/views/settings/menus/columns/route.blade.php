<div class="d-flex flex-column">
    @if($row->route_name)
        <span class="fw-semibold"><code>{{ $row->route_name }}</code></span>
        @if(!empty($row->route_parameters))
            <span class="small text-muted">
                {{ json_encode($row->route_parameters) }}
            </span>
        @endif
    @endif
    @if($row->url)
        <span class="small text-muted">{{ $row->url }}</span>
    @endif
</div>
