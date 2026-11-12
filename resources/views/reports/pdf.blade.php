<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 13mm 18mm; }
        body { margin: 0; color: #202b2e; font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.4; }
        .masthead { margin: -14mm -13mm 8mm; padding: 7mm 13mm; background: #192629; color: #fff; }
        .masthead-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .masthead-table td { padding: 0; vertical-align: middle; }
        .logo-cell { width: 22mm; }
        .logo { display: block; width: 18mm; height: 18mm; }
        .brand-cell { padding-right: 4mm !important; }
        .brand { font-size: 21pt; font-weight: bold; letter-spacing: 1.5px; white-space: nowrap; }
        .tag { color: #c5aa67; font-size: 7pt; font-weight: bold; letter-spacing: .5px; white-space: nowrap; }
        .package-cell { width: 60mm; text-align: right; }
        .package { display: block; width: 52mm; padding: 2.5mm 2mm; border: 1px solid #c5aa67; color: #c5aa67; font-size: 8pt; font-weight: bold; line-height: 1.25; text-align: center; overflow-wrap: break-word; }
        h1 { margin: 0 0 2mm; font-size: 16pt; text-transform: uppercase; }
        .sub { margin: 0 0 5mm; color: #687176; }
        .vehicle { width: 100%; margin: 0 0 6mm; border-collapse: collapse; background: #f3f1ea; }
        .vehicle td { width: 33%; padding: 3mm; vertical-align: top; }
        .label { color: #687176; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .value { font-size: 11pt; font-weight: bold; }
        .table-head { width: 100%; border-collapse: collapse; background: #e9e5d9; }
        .table-head td { padding: 2mm; color: #515d60; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .item { width: 35%; } .result { width: 25%; } .comment { width: 40%; }
        .check { width: 100%; margin: 0 0 2mm; border-collapse: collapse; page-break-inside: avoid; }
        .check td { padding: 3mm 2mm; border-bottom: 1px solid #dcdedb; vertical-align: top; }
        .question { font-weight: bold; }
        .measurement { margin-top: 1mm; color: #687176; font-size: 8pt; }
        .rating { font-weight: bold; text-transform: uppercase; }
        .bars { width: 100%; margin-top: 2mm; border-spacing: 1mm 0; }
        .bars td { height: 2mm; padding: 0; background: #d6dad8; }
        .bars td.active { background: #c3a25c; }
        .notes { white-space: pre-wrap; }
        .section { margin: 5mm 0 2mm; padding: 2mm 3mm; border-left: 3px solid #c3a25c; background: #e9e5d9; font-size: 11pt; font-weight: bold; page-break-after: avoid; }
        .rating-key { width: 100%; margin-top: 6mm; padding: 3mm; border-collapse: collapse; table-layout: fixed; background: #192629; color: white; page-break-inside: avoid; }
        .rating-key td { width: 25%; padding: 2mm 2.5mm; font-size: 8pt; white-space: nowrap; }
        .demo { margin-bottom: 4mm; padding: 3mm; border: 1px solid #bb913d; background: #fff7df; }
        .video { margin-top: 5mm; padding: 3mm; background: #f3f1ea; }
        .photos { page-break-before: always; }
        .photo { margin: 6mm 0; text-align: center; page-break-inside: avoid; }
        .photo img { max-width: 100%; max-height: 220mm; }
        .caption { color: #687176; font-size: 8pt; }
    </style>
</head>
<body>
    <header class="masthead">
        <table class="masthead-table"><tr>
            @if($logoData)<td class="logo-cell"><img class="logo" src="{{ $logoData }}" alt="Cerberus"></td>@endif
            <td class="brand-cell"><div class="brand">CERBERUS</div><div class="tag">PRE-PURCHASE VEHICLE INSPECTIONS</div></td>
            <td class="package-cell"><div class="package">{{ $inspection->package_snapshot['name'] ?? 'Inspection' }}</div></td>
        </tr></table>
    </header>
    @if ($inspection->package_snapshot['is_demo'] ?? false)<div class="demo"><strong>DEMO REPORT — NOT FOR CUSTOMER USE.</strong> Package price/checklist data may be illustrative and unapproved.</div>@endif
    <h1>Inspection results</h1>
    <p class="sub">{{ $inspection->booking->booking_reference }} &nbsp;·&nbsp; Report v{{ $version }}</p>
    <table class="vehicle"><tr>
        <td><span class="label">Vehicle</span><br><span class="value">{{ trim(($inspection->booking->vehicle->year ? $inspection->booking->vehicle->year.' ' : '').($inspection->booking->vehicle->make ?? '').' '.($inspection->booking->vehicle->model ?? '')) ?: 'Vehicle details not supplied' }}</span></td>
        <td><span class="label">Registration</span><br><span class="value">{{ $inspection->booking->vehicle->registration }}</span></td>
        <td><span class="label">Mileage</span><br><span class="value">{{ $inspection->booking->vehicle->mileage_display ?: 'Not supplied' }}</span></td>
    </tr></table>
    <table class="table-head"><tr><td class="item">Item</td><td class="result">Result</td><td class="comment">Evidence / comment</td></tr></table>
    @foreach ($inspection->checks->groupBy('section_title_snapshot') as $section => $checks)
        <div class="section">{{ $section }}</div>
        @foreach ($checks as $check)
            @php($rating = collect($inspection->rating_scheme_snapshot)->firstWhere('code', $check->rating_code))
            @php($score = (int) ($rating['score'] ?? 0))
            @php($ratingColor = match ($check->rating_code) {'good' => '#27865e', 'adequate' => '#e28a2b', 'warning' => '#e1c93f', 'poor' => '#b4363a', default => ($rating['color'] ?? '#c3a25c')})
            <table class="check"><tr>
                <td class="item"><div class="question">{{ $check->question_text_snapshot }}</div>@if($check->measurements)<div class="measurement">{{ $check->measurements }}</div>@endif</td>
                <td class="result"><div class="rating">{{ $rating['label'] ?? ($check->rating_code ? str($check->rating_code)->replace('_', ' ')->upper() : 'Not recorded') }}</div>
                    @if($rating)<table class="bars"><tr>@for($bar = 1; $bar <= 4; $bar++)<td style="{{ $bar <= $score ? 'background-color: '.$ratingColor.' !important;' : 'background-color: #d6dad8 !important;' }}"></td>@endfor</tr></table>@endif
                </td>
                <td class="comment"><div class="notes">{{ $check->notes ?: '—' }}</div></td>
            </tr></table>
        @endforeach
    @endforeach
    @php($scoredRatings = collect($inspection->rating_scheme_snapshot)->filter(fn (array $rating) => isset($rating['score']))->values())
    <table class="rating-key"><tr>@foreach($scoredRatings as $rating)<td><strong>{{ strtoupper($rating['label']) }}</strong> &nbsp; {{ $rating['score'] }}/4</td>@endforeach</tr><tr><td colspan="4">Not tested and not applicable are shown separately, without a score.</td></tr></table>
    @if($inspection->video)<div class="video"><strong>Inspection video:</strong> {{ $inspection->video->source_url }}</div>@endif
    @if($photos->isNotEmpty())<section class="photos"><h1>Photographic evidence</h1>@foreach($photos as $photo)<figure class="photo"><img src="{{ $photo->inline_data }}" alt="{{ $photo->caption ?: $photo->original_filename }}"><figcaption class="caption">{{ $photo->caption ?: $photo->original_filename }}</figcaption></figure>@endforeach</section>@endif
</body>
</html>
