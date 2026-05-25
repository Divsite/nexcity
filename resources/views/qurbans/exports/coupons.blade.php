@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $organization = $batch->organization;
    $logoUrl = $organization?->profile?->logo_url;
    $couponColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $batch->coupon_color) ? $batch->coupon_color : '#111111';
    $location = trim(implode(' / ', array_filter([
        $batch->neighborhoodAssociation?->name,
        $batch->citizensAssociation?->name,
    ])));
    $claimDate = $batch->claim_starts_at?->format('d/m/Y');
    $claimTime = $batch->claim_starts_at?->format('H:i');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.qurban_coupons') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .coupon-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .coupon-cell {
            width: 33.333%;
            padding: 1mm 1mm 2mm;
            vertical-align: top;
        }
        .coupon-box {
            width: 61mm;
            height: 40mm;
            border: 1px dashed #999;
            padding: 1.6mm;
            page-break-inside: avoid;
            font-size: 10px;
        }
        .coupon-inner {
            border: 2px solid {{ $couponColor }};
            border-left-width: 5px;
            height: 37mm;
            padding: 1.1mm;
            overflow: hidden;
        }
        .page-break {
            page-break-after: always;
        }
        .brand {
            display: table;
            width: 100%;
            margin-bottom: 0.8mm;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.7mm;
        }
        .brand-logo {
            display: table-cell;
            width: 9mm;
            vertical-align: middle;
        }
        .brand-logo img {
            width: 7.5mm;
            height: 7.5mm;
            object-fit: contain;
        }
        .brand-name {
            display: table-cell;
            vertical-align: middle;
            font-weight: bold;
            font-size: 8.5px;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .qr {
            float: left;
            width: 17.5mm;
            margin-right: 1.6mm;
        }
        .qr img {
            width: 17.5mm;
            height: 17.5mm;
        }
        .title {
            font-weight: bold;
            font-size: 9px;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .code {
            font-weight: bold;
            font-size: 8px;
            margin-top: 1px;
        }
        .meta {
            font-size: 7px;
            color: #333;
            line-height: 1.15;
            margin-top: 2px;
        }
        .notes {
            display: block;
            max-height: 7mm;
            overflow: hidden;
            margin-top: 1mm;
            font-weight: bold;
            font-style: italic;
            color: #111;
        }
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
    </style>
</head>
<body>
@foreach($coupons->chunk(18) as $page)
    <table class="coupon-table">
        @foreach($page->chunk(3) as $row)
            <tr>
                @foreach($row as $coupon)
                    <td class="coupon-cell">
                        <div class="coupon-box">
                            <div class="coupon-inner">
                                <div class="brand">
                                    <div class="brand-logo">
                                        @if($logoUrl)
                                            <img src="{{ $logoUrl }}" alt="{{ $organization?->name }}">
                                        @endif
                                    </div>
                                    <div class="brand-name">{{ $organization?->name }}</div>
                                </div>
                                <div class="qr">
                                    @php($qrSvg = QrCode::format('svg')->size(90)->margin(1)->generate($coupon->qr_code ?: $coupon->coupon_code))
                                    <img src="data:image/svg+xml;base64,{{ base64_encode((string) $qrSvg) }}" alt="{{ $coupon->coupon_code }}">
                                </div>
                                <div class="title">{{ __('messages.qurban_meat_coupon') }}</div>
                                <div class="code">{{ $coupon->coupon_code }}</div>
                                <div class="meta">
                                    @if($location){{ __('messages.location') }}: {{ $location }}<br>@endif
                                    @if($claimDate || $claimTime){{ __('messages.claim_date') }}: {{ $claimDate ?: '-' }} - {{ $claimTime ?: '-' }}<br>@endif
                                    @if($coupon->beneficiary?->name_snapshot){{ __('messages.beneficiary') }}: {{ $coupon->beneficiary->name_snapshot }}<br>@endif
                                    @if($coupon->package_label){{ __('messages.package_label') }}: {{ $coupon->package_label }}@endif
                                    @if($batch->notes)
                                        <span class="notes">{{ $batch->notes }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                @endforeach
                @for($i = $row->count(); $i < 3; $i++)
                    <td class="coupon-cell"></td>
                @endfor
            </tr>
        @endforeach
    </table>

    @if(! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
