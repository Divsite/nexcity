@php
    $organization = $transaction->organization;
    $logoUrl = $organization?->profile?->logo_url;
    $moneyTotal = $transaction->detailMoneyAmount();
    $riceTotal = $transaction->detailRiceAmount();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.print_invoice') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
        .receipt { width: 58mm; margin: 0 auto; }
        .center { text-align: center; }
        .logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            padding: 4px;
            border-radius: 4px;
            margin: 0 auto 6px;
        }
        .logo {
            display: block;
            max-width: 20mm;
            max-height: 20mm;
            object-fit: contain;
        }
        .org-name { font-weight: 700; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .label { width: 45%; }
        .value { text-align: right; }
        .total { font-weight: bold; }
        @media print {
            body { margin: 0; }
            .receipt { width: 58mm; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center">
            @if($logoUrl)
                <div class="logo-wrap">
                    <img src="{{ $logoUrl }}" class="logo" alt="{{ $organization?->name }}">
                </div>
            @endif
            <div class="org-name">{{ $organization?->name ?? '-' }}</div>
            <div>{{ $organization?->mosqueProfile?->address_line ?? $organization?->profile?->address_line ?? '' }}</div>
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="label">{{ __('messages.invoice_number') }}</td>
                <td class="value">#{{ $transaction->id }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.date') }}</td>
                <td class="value">{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.payer') }}</td>
                <td class="value">{{ $transaction->payer_name }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.charity_type') }}</td>
                <td class="value">{{ $transaction->charityType?->source?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.payment_method') }}</td>
                <td class="value">
                    {{ \App\Models\Charities\CharityTransaction::paymentMethodLabels()[$transaction->payment_method] ?? $transaction->payment_method }}
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            @if($moneyTotal > 0)
                <tr>
                    <td class="label">{{ __('messages.total_money') }}</td>
                    <td class="value total">{{ \Cknow\Money\Money::IDR($moneyTotal)->format(app()->getLocale()) }}</td>
                </tr>
            @endif
            @if($riceTotal > 0)
                <tr>
                    <td class="label">{{ __('messages.total_rice') }}</td>
                    <td class="value total">{{ \Illuminate\Support\Number::format($riceTotal, 2, 2, app()->getLocale()) }} {{ __('messages.liter') }}</td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        <div class="center">{{ __('messages.invoice_thank_you') }}</div>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
