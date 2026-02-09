@php
    $contexts = [
        'admin' => 'Admin',
        'rt' => 'RT',
        'mosque' => 'Mosque',
        'resident' => 'Resident',
    ];
@endphp
<span class="badge bg-light text-dark border">
    {{ $contexts[$row->context] ?? \Illuminate\Support\Str::upper($row->context) }}
</span>
