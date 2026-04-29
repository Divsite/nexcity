@php
    use Illuminate\Support\Number;

    $distributionTitle = $distribution?->title ?: __('messages.distributions');
    $distributionLocation = trim(implode(' / ', array_filter([
        $distribution?->neighborhoodAssociation?->name,
        $distribution?->citizensAssociation?->name,
    ])));
    $distributionClass = $models->pluck('distributionClass.source.name')
        ->filter()
        ->unique()
        ->values()
        ->implode(', ');
    $officerNames = collect($distribution?->officers ?? [])
        ->map(fn ($item) => $item->officer?->name)
        ->filter()
        ->unique()
        ->values()
        ->implode(', ');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.recipients') }}</title>
    <style>
        html, body {
            font-size: 12px;
        }
        table, th, td {
            border: 1px solid #000;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px;
            vertical-align: top;
        }
        .text-end {
            text-align: right;
        }
        .meta {
            margin-bottom: 12px;
        }
        .meta p {
            margin: 0 0 4px;
        }
    </style>
</head>
<body>
<div style="text-align: right; font-size:10px; color:grey">
    <p>{{ __('messages.generated_at', ['date' => now()->format('d/m/Y h:i A')]) }}</p>
</div>

<h3 style="text-align: center; margin-bottom: 12px;">
    {{ $distributionTitle }} - {{ __('messages.recipients') }}
</h3>

<div class="meta">
    <p><strong>{{ __('messages.year') }}:</strong> {{ $distribution?->year ?? '-' }}</p>
    <p><strong>{{ __('messages.location') }}:</strong> {{ $distributionLocation !== '' ? $distributionLocation : '-' }}</p>
    <p><strong>{{ __('messages.distribution_class') }}:</strong> {{ $distributionClass !== '' ? $distributionClass : '-' }}</p>
    <p><strong>{{ __('messages.officers') }}:</strong> {{ $officerNames !== '' ? $officerNames : '-' }}</p>
</div>

<table style="width: 100%;">
    <thead>
    <tr>
        <th>{{ __('No.') }}</th>
        <th>{{ __('messages.recipient') }}</th>
        <th>{{ __('messages.total_money') }}</th>
        <th>{{ __('messages.total_rice') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse($models as $model)
        @php
            $recipientName = $model->resident?->name
                ?? $model->officer?->name
                ?? ($model->recipient_name ? $model->recipient_name . ' (' . __('messages.manual') . ')' : '-');
            $money = (float) ($model->amount_money ?? $model->distributionClass?->get_money ?? 0);
            $rice = (float) ($model->amount_rice ?? $model->distributionClass?->get_rice ?? 0);
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $recipientName }}</td>
            <td class="text-end">IDR {{ Number::format($money, 2, 2, app()->currentLocale()) }}</td>
            <td class="text-end">{{ Number::format($rice, 2, 2, app()->currentLocale()) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" style="text-align: center;">{{ __('messages.no_data_available') }}</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
