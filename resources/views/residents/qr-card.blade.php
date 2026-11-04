@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $year = now()->year;
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Warga</title>
    <style>
        * { margin: 0; padding: 0; }

        /* The page *is* the card, so no page margin at all. */
        @page { margin: 0; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }

        /* Table layout, not flexbox: DomPDF has no flex support, and a flex row
           silently stacks instead — which pushed every card onto two pages. */
        table.card {
            width: 85.6mm;
            border-collapse: collapse;
            /* No fixed height: at exactly page height the table tipped onto a
               second page. Content height plus @page margin 0 fills the card. */
            page-break-inside: avoid;
        }
        /* Break *between* cards only — putting it on every card leaves a blank
           final page. */
        table.card.has-next { page-break-after: always; }
        table.card td { vertical-align: top; }

        /* White behind the QR: scanners need the quiet zone and the contrast.
           A tinted panel is the most common reason a printed code fails. */
        img.qr { width: 28mm; height: 28mm; }
        td.qr-side {
            width: 34mm;
            background: #ffffff;
            text-align: center;
            padding: 4mm 2mm;
        }
        td.info-side {
            background: #1a3a5c;
            color: #ffffff;
            padding: 4mm;
        }

        .brand {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9fb3c8;
            padding-bottom: 1.5mm;
        }
        .resident-name {
            font-size: 11px;
            font-weight: bold;
            line-height: 1.3;
            padding-bottom: 1.5mm;
        }
        .location {
            font-size: 8px;
            color: #d6e0ea;
            padding-bottom: 1mm;
        }
        .code {
            font-size: 8px;
            letter-spacing: 1px;
            color: #9fb3c8;
        }
        .note {
            font-size: 6px;
            color: #7d93a8;
            padding-top: 3mm;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@foreach($residents as $resident)
    @php
        $profile = $resident->residentProfile;
        $location = trim(implode(' / ', array_filter([
            $profile->neighborhoodAssociation?->name,
            $profile->citizensAssociation?->name,
        ])));

        // Level M survives a scratched, folded or dusty card without inflating
        // the code so much that it stops fitting the panel.
        // DomPDF ignores inline <svg>, but does render SVG given as an <img>
        // source. PNG is not an option here: simple-qrcode needs imagick for
        // that, and only GD is installed.
        $qrSvg = QrCode::format('svg')
            ->size(300)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($profile->qr_token);

        $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        // A short lookup code, not the full UUID. Nobody types a UUID by hand,
        // and printing it in full only makes the token readable from a photo of
        // the card for no practical gain.
        $shortCode = strtoupper(substr(str_replace('-', '', $profile->qr_token), 0, 8));
    @endphp
    <table class="card @if(! $loop->last)has-next @endif">
        <tr>
            <td class="qr-side"><img class="qr" src="{{ $qrDataUri }}" alt="QR"></td>
            <td class="info-side">
                <div class="brand">Kartu Warga &middot; {{ $year }}</div>
                <div class="resident-name">{{ $resident->name }}</div>
                @if($location)
                    <div class="location">{{ $location }}</div>
                @endif
                <div class="code">{{ $shortCode }}</div>
                <div class="note">Tunjukkan kartu ini saat distribusi</div>
            </td>
        </tr>
    </table>
@endforeach
</body>
</html>
