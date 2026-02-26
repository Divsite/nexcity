@php
    use Cknow\Money\Money;
    use Illuminate\Support\Number;
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('messages.charity_transactions') }}</title>
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
        }
        .text-end {
            text-align: right;
        }
    </style>
</head>
<body>
<div style="text-align: right; font-size:10px; color:grey">
    <p>{{ __('messages.generated_at', ['date' => now()->format('d/m/Y h:i A')]) }}</p>
</div>

<h3 style="text-align: center; margin-bottom: 18px;">
    {{ __('messages.charity_transactions') }}
</h3>

<table style="width: 100%;">
    <thead>
    <tr>
        <th>{{ __('No.') }}</th>
        <th>{{ __('messages.charity_type') }}</th>
        <th>{{ __('messages.payer') }}</th>
        <th>{{ __('messages.payment_method') }}</th>
        <th>{{ __('messages.total_money') }}</th>
        <th>{{ __('messages.total_rice') }}</th>
        <th>{{ __('messages.status') }}</th>
        <th>{{ __('messages.created_at') }}</th>
        <th>{{ __('messages.updated_at') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($models as $model)
        @php
            $typeName = $model->charityType?->source?->name ?? '-';
            $typeYear = $model->charityType?->year ?? $model->year;
            $name = trim((string) ($model->payer_name ?? ''));
            $memberCount = (int) ($model->package_members_count ?: ($model->payers_count ?? 0));
            $payerLabel = $name !== '' ? $name : '-';
            if ($model->is_package && $memberCount > 0) {
                $payerLabel = $payerLabel . ' (' . __('messages.family_members_count') . ': ' . $memberCount . ')';
            }
            $paymentLabel = $model->payment_method ? __('messages.' . $model->payment_method) : '-';
            $totalMoney = $model->detailMoneyAmount();
            $totalRice = $model->detailRiceAmount();
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $typeName }}{{ $typeYear ? ' - ' . $typeYear : '' }}</td>
            <td>{{ $payerLabel }}</td>
            <td>{{ $paymentLabel }}</td>
            <td class="text-end">{{ Money::IDR($totalMoney)->format(app()->currentLocale()) }}</td>
            <td class="text-end">{{ Number::format($totalRice, 2, 2, app()->currentLocale()) }}</td>
            <td>{{ $model->statusLabel() }}</td>
            <td>{{ $model->created_at ? $model->created_at->format('d/m/Y h:i A') : '-' }}</td>
            <td>{{ $model->updated_at ? $model->updated_at->diffForHumans() : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
