@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    // Flatten all coupons from all batches into one list with their batch metadata attached.
    $allItems = collect();
    foreach ($batches as $batch) {
        $org       = $batch->organization;
        $meta = [
            'logoUrl'      => $org?->profile?->logo_url,
            'orgName'      => $org?->name,
            'couponColor'  => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $batch->coupon_color) ? $batch->coupon_color : '#111111',
            'location'     => trim(implode(' / ', array_filter([
                                  $batch->neighborhoodAssociation?->name,
                                  $batch->citizensAssociation?->name,
                              ]))),
            'claimDate'    => $batch->claim_starts_at?->format('d/m/Y'),
            'claimTime'    => $batch->claim_starts_at?->format('H:i'),
            'notes'        => $batch->notes,
        ];
        foreach ($batch->coupons as $coupon) {
            $allItems->push(['coupon' => $coupon, 'meta' => $meta]);
        }
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.qurban_coupons') }}</title>
    <style>
        * { box-sizing: border-box; }
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
            min-height: 18mm;
            border: 1px dashed #999;
            padding: 1.6mm;
            page-break-inside: avoid;
            font-size: 10px;
        }
        .page-break { page-break-after: always; }
        .brand {
            display: table;
            width: 100%;
            margin-bottom: 0.8mm;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.7mm;
        }
        .brand-logo { display: table-cell; width: 9mm; vertical-align: middle; }
        .brand-logo img { width: 7.5mm; height: 7.5mm; object-fit: contain; }
        .brand-name {
            display: table-cell;
            vertical-align: middle;
            font-weight: bold;
            font-size: 8.5px;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .qr { float: left; width: 17.5mm; margin-right: 1.6mm; }
        .qr img { width: 17.5mm; height: 17.5mm; }
        .title { font-weight: bold; font-size: 9px; line-height: 1.15; text-transform: uppercase; }
        .code { font-weight: bold; font-size: 8px; margin-top: 1px; }
        .meta { font-size: 7px; color: #333; line-height: 1.15; margin-top: 2px; }
        .notes {
            display: block;
            max-height: 7mm;
            overflow: hidden;
            margin-top: 1mm;
            font-weight: bold;
            font-style: italic;
            color: #111;
        }
        @page { size: A4 portrait; margin: 6mm; }
    </style>
</head>
<body>
@foreach($allItems->chunk(21) as $page)
    <table class="coupon-table">
        @foreach($page->chunk(3) as $row)
            <tr>
                @foreach($row as $item)
                    @php $coupon = $item['coupon']; $m = $item['meta']; @endphp
                    <td class="coupon-cell">
                        <div class="coupon-box">
                            <div style="border: 2px solid {{ $m['couponColor'] }}; border-left-width: 5px; padding: 1.1mm;">
                                <div class="brand">
                                    <div class="brand-logo">
                                        @if($m['logoUrl'])
                                            <img src="{{ $m['logoUrl'] }}" alt="{{ $m['orgName'] }}">
                                        @endif
                                    </div>
                                    <div class="brand-name">{{ $m['orgName'] }}</div>
                                </div>
                                <div class="qr">
                                    @php($qrSvg = QrCode::format('svg')->size(90)->margin(1)->generate($coupon->qr_code ?: $coupon->coupon_code))
                                    <img src="data:image/svg+xml;base64,{{ base64_encode((string) $qrSvg) }}" alt="{{ $coupon->coupon_code }}">
                                </div>
                                <div class="title">{{ __('messages.qurban_meat_coupon') }}</div>
                                <div class="code">{{ $coupon->coupon_code }}</div>
                                <div class="meta">
                                    @if($m['location']){{ __('messages.inhabitant') }}: {{ $m['location'] }}<br>@endif
                                    @if($m['claimDate'] || $m['claimTime']){{ __('messages.claim_date') }}: {{ $m['claimDate'] ?: '-' }} - {{ $m['claimTime'] ?: '-' }}<br>@endif
                                    @if($coupon->beneficiary?->name_snapshot){{ __('messages.beneficiary') }}: {{ $coupon->beneficiary->name_snapshot }}<br>@endif
                                    @if($coupon->package_label){{ __('messages.package_label') }}: {{ $coupon->package_label }}@endif
                                    @if($m['notes'])
                                        <span class="notes">{{ $m['notes'] }}</span>
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
