@php
    /** @var \App\Models\Template $template */
    $w = $template->width_mm;
    $h = $template->height_mm;
    $fontFamilies = [
        'display' => "'Playfair Display', serif",
        'sans' => "'Plus Jakarta Sans', sans-serif",
        'mono' => "'DejaVu Sans Mono', monospace",
    ];
    $mm = fn ($value) => round((float) $value, 3).'mm';
    // Teks dalam pita berlatar (mis. peran): ditengahkan vertikal dengan padding.
    $pill = function (array $field) use ($mm) {
        $textMm = (float) ($field['size'] ?? 8) * 0.3528 * 1.15;
        $pad = max(0, ((float) $field['h'] - $textMm) / 2);

        return 'background: '.$field['bg'].'; line-height: 1.15; padding-top: '.$mm($pad).'; height: '.$mm((float) $field['h'] - $pad).';';
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'Playfair Display'; font-weight: 600; font-style: normal; src: url('{{ $fontDir }}/PlayfairDisplay_600SemiBold.ttf'); }
    @font-face { font-family: 'Playfair Display'; font-weight: 700; font-style: normal; src: url('{{ $fontDir }}/PlayfairDisplay_700Bold.ttf'); }
    @font-face { font-family: 'Playfair Display'; font-weight: 400; font-style: italic; src: url('{{ $fontDir }}/PlayfairDisplay_400Regular_Italic.ttf'); }
    @font-face { font-family: 'Playfair Display'; font-weight: 700; font-style: italic; src: url('{{ $fontDir }}/PlayfairDisplay_700Bold_Italic.ttf'); }
    @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 400; font-style: normal; src: url('{{ $fontDir }}/PlusJakartaSans_400Regular.ttf'); }
    @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 500; font-style: normal; src: url('{{ $fontDir }}/PlusJakartaSans_500Medium.ttf'); }
    @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 600; font-style: normal; src: url('{{ $fontDir }}/PlusJakartaSans_600SemiBold.ttf'); }
    @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 700; font-style: normal; src: url('{{ $fontDir }}/PlusJakartaSans_700Bold.ttf'); }
    @font-face { font-family: 'Plus Jakarta Sans'; font-weight: 800; font-style: normal; src: url('{{ $fontDir }}/PlusJakartaSans_800ExtraBold.ttf'); }

    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .page { position: relative; overflow: hidden; page-break-after: always; }
    .page.last { page-break-after: auto; }
    .card { position: absolute; overflow: hidden; width: {{ $mm($w) }}; height: {{ $mm($h) }}; }
    .bg { position: absolute; left: 0; top: 0; width: {{ $mm($w) }}; height: {{ $mm($h) }}; }
    .f { position: absolute; overflow: hidden; }
    .t { position: absolute; }
    .cut { position: absolute; background: #9a8a84; }
</style>
</head>
<body>
@foreach ($pages as $cards)
    @php $lastPage = $loop->last; @endphp
    @if ($mode === 'sheet')
        <div class="page {{ $lastPage ? 'last' : '' }}" style="width: 210mm; height: 297mm;">
            {{-- Tanda potong di tepi setiap kolom dan baris kartu (FR-GEN-06). --}}
            @for ($c = 0; $c <= $grid['cols']; $c++)
                @php $x = $grid['left'] + $c * $w; @endphp
                <div class="cut" style="left: {{ $mm($x - 0.1) }}; top: {{ $mm($grid['top'] - 5) }}; width: 0.2mm; height: 4mm;"></div>
                <div class="cut" style="left: {{ $mm($x - 0.1) }}; top: {{ $mm($grid['top'] + $grid['rows'] * $h + 1) }}; width: 0.2mm; height: 4mm;"></div>
            @endfor
            @for ($r = 0; $r <= $grid['rows']; $r++)
                @php $y = $grid['top'] + $r * $h; @endphp
                <div class="cut" style="left: {{ $mm($grid['left'] - 5) }}; top: {{ $mm($y - 0.1) }}; width: 4mm; height: 0.2mm;"></div>
                <div class="cut" style="left: {{ $mm($grid['left'] + $grid['cols'] * $w + 1) }}; top: {{ $mm($y - 0.1) }}; width: 4mm; height: 0.2mm;"></div>
            @endfor
    @endif

    @foreach ($cards as $cardIndex => $card)
        @php
            $left = $mode === 'sheet' ? $grid['left'] + ($cardIndex % $grid['cols']) * $w : 0;
            $top = $mode === 'sheet' ? $grid['top'] + intdiv($cardIndex, $grid['cols']) * $h : 0;
            $subject = $card['subject'];
        @endphp
        @if ($mode !== 'sheet')
            <div class="page {{ $lastPage ? 'last' : '' }}" style="width: {{ $mm($w) }}; height: {{ $mm($h) }};">
        @endif
        <div class="card" style="left: {{ $mm($left) }}; top: {{ $mm($top) }};">
            @if ($background)
                <img class="bg" src="{{ $background }}" alt="">
            @endif

            @foreach ($fields as $field)
                @php
                    $box = 'left: '.$mm($field['x']).'; top: '.$mm($field['y']).'; width: '.$mm($field['w']).'; height: '.$mm($field['h']).';';
                    $radius = ! empty($field['radius']) ? 'border-radius: '.$mm($field['radius']).';' : '';
                @endphp
                @if ($field['kind'] === 'photo')
                    @php $photo = $subject->photoDataUri ? \App\Services\Pdf\PhotoCropper::cover($subject->photoDataUri, (float) $field['w'], (float) $field['h']) : null; @endphp
                    <div class="f" style="{{ $box }} {{ $radius }} background: #e9d7c4; {{ ! empty($field['border']) ? 'border: 0.5mm solid '.$field['border'].';' : '' }}">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" style="width: 100%; height: 100%;">
                        @endif
                    </div>
                @elseif ($field['kind'] === 'qr')
                    <img class="f" src="{{ $card['qr'] }}" alt="" style="{{ $box }} {{ $radius }}">
                @else
                    @php
                        $text = $subject->interpolate((string) ($field['content'] ?? ''));
                        $style = $box.$radius
                            .'font-family: '.($fontFamilies[$field['font'] ?? 'sans'] ?? $fontFamilies['sans']).';'
                            .'font-weight: '.((int) ($field['weight'] ?? 400)).';'
                            .'font-style: '.(! empty($field['italic']) ? 'italic' : 'normal').';'
                            .'font-size: '.\App\Services\Pdf\TextFit::size($text, $field).'pt;'
                            .'color: '.($field['color'] ?? '#2A1416').';'
                            .'text-align: '.($field['align'] ?? 'center').';'
                            .(! empty($field['uppercase']) ? 'text-transform: uppercase;' : '')
                            .(! empty($field['letter_spacing']) ? 'letter-spacing: '.round((float) $field['letter_spacing'], 3).'em;' : '')
                            .(! empty($field['bg']) ? $pill($field) : 'line-height: 1.2;')
                            .'white-space: pre-line;';
                    @endphp
                    <div class="t" style="{{ $style }}">{{ $text }}</div>
                @endif
            @endforeach
        </div>
        @if ($mode !== 'sheet')
            </div>
        @endif
    @endforeach

    @if ($mode === 'sheet')
        </div>
    @endif
@endforeach
</body>
</html>
