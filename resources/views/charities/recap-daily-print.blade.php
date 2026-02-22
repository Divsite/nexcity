<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.daily_recap') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h2, h4 { margin: 0 0 8px 0; }
        .muted { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; }
        th { background: #f3f4f6; text-align: left; }
        td.text-end, th.text-end { text-align: right; }
        .summary { margin-top: 14px; }
        .actions { margin-bottom: 16px; }
        @media print {
            .actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">{{ __('messages.print') }}</button>
    </div>

    <h2>{{ __('messages.daily_recap') }}</h2>
    <div class="muted">
        {{ $recapDate->translatedFormat('d F Y') }}
        @if($organizationName)
            · {{ $organizationName }}
        @endif
    </div>

    <table>
        <thead>
        <tr>
            <th>{{ __('messages.charity_type') }}</th>
            <th class="text-end">{{ __('messages.transactions') }}</th>
            <th class="text-end">{{ __('messages.total_money') }}</th>
            <th class="text-end">{{ __('messages.total_rice') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="text-end">{{ $row['count'] }}</td>
                <td class="text-end">{{ $row['total_money_label'] }}</td>
                <td class="text-end">{{ number_format((float) $row['total_rice'], 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-end">{{ __('messages.data_not_found') }}</td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <th>{{ __('messages.total') }}</th>
            <th class="text-end">{{ $totalCount }}</th>
            <th class="text-end">{{ $totalMoneyLabel }}</th>
            <th class="text-end">{{ number_format((float) $totalRice, 2, ',', '.') }}</th>
        </tr>
        </tfoot>
    </table>
</body>
</html>
