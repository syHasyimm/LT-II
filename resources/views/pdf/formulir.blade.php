@php
    /** @var \App\Models\Gudep $gudep */
    $v = fn ($value) => filled($value) ? $value : '-';
    $birth = fn ($member) => $member->birth_date?->format('d/m/Y') ?? '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Formulir Pendaftaran {{ $gudep->name }}</title>
<style>
    @page { margin: 14mm 16mm 16mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1f1a17; }
    .kop { width: 100%; border-collapse: collapse; }
    .kop td { vertical-align: middle; }
    .kop .logo { width: 24mm; text-align: center; }
    .kop .logo img { height: 22mm; }
    .kop .text { text-align: center; line-height: 1.2; }
    .kop .title { font-size: 15pt; font-weight: bold; letter-spacing: 0.3pt; }
    .kop .subtitle { font-size: 17pt; font-weight: bold; }
    .kop .small { font-size: 9pt; }
    .rule { border-top: 2.4pt solid #000; border-bottom: 0.8pt solid #000; height: 1.6pt; margin: 3mm 0 6mm; }
    h1 { text-align: center; font-size: 13pt; color: #7a1219; margin: 0 0 5mm; text-transform: uppercase; letter-spacing: 0.5pt; }
    h2 { font-size: 10.5pt; color: #7a1219; margin: 6mm 0 2mm; border-bottom: 0.6pt solid #c9a24a; padding-bottom: 1mm; }
    .identity { width: 100%; border-collapse: collapse; }
    .identity td { padding: 1.2mm 0; vertical-align: top; }
    .identity .label { width: 42mm; color: #5b524c; }
    .identity .colon { width: 4mm; }
    table.list { width: 100%; border-collapse: collapse; }
    table.list th, table.list td { border: 0.6pt solid #9a918a; padding: 1.4mm 2mm; text-align: left; vertical-align: top; }
    table.list th { background: #f3ece0; font-size: 8.5pt; }
    table.list .no { width: 8mm; text-align: center; }
    .empty { color: #5b524c; font-style: italic; }
    .dates { margin-top: 7mm; font-size: 9pt; color: #5b524c; }
</style>
</head>
<body>
    <table class="kop">
        <tr>
            <td class="logo">@if ($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif</td>
            <td class="text">
                <div class="title">{{ $kop['title'] }}</div>
                <div class="subtitle">{{ $kop['subtitle'] }}</div>
                <div class="small">{{ $kop['address'] }}</div>
                <div class="small">{{ $kop['email'] }}</div>
            </td>
            <td class="logo">@if ($logoRight)<img src="{{ $logoRight }}" alt="">@endif</td>
        </tr>
    </table>
    <div class="rule"></div>

    <h1>Formulir Pendaftaran Perkemahan LT 2</h1>

    <h2>Identitas Gudep</h2>
    <table class="identity">
        <tr><td class="label">Nama gudep</td><td class="colon">:</td><td>{{ $v($gudep->name) }}</td></tr>
        <tr><td class="label">Nomor gudep</td><td class="colon">:</td><td>{{ $v($gudep->gudep_number) }}</td></tr>
        <tr><td class="label">NPSN</td><td class="colon">:</td><td>{{ $v($gudep->npsn) }}</td></tr>
        <tr><td class="label">Nama instansi</td><td class="colon">:</td><td>{{ $v($gudep->institution) }}</td></tr>
        <tr><td class="label">Penanggung jawab</td><td class="colon">:</td><td>{{ $v($gudep->pic_name) }}</td></tr>
        <tr><td class="label">Alamat</td><td class="colon">:</td><td>{{ $v($gudep->address) }}</td></tr>
        <tr><td class="label">Pemilik akun</td><td class="colon">:</td><td>{{ $v($gudep->user?->name) }}</td></tr>
    </table>

    @foreach (['Peserta Putra' => $putra, 'Peserta Putri' => $putri] as $title => $list)
        <h2>{{ $title }} ({{ $list->count() }})</h2>
        <table class="list">
            <thead><tr><th class="no">No</th><th>Nama</th><th style="width: 30mm">Tanggal lahir</th><th style="width: 38mm">No. KTA</th></tr></thead>
            <tbody>
                @forelse ($list as $member)
                    <tr><td class="no">{{ $loop->iteration }}</td><td>{{ $member->name }}</td><td>{{ $birth($member) }}</td><td>{{ $v($member->kta_number) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">Belum ada peserta.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <h2>Pembina Pendamping ({{ $pembina->count() }})</h2>
    <table class="list">
        <thead><tr><th class="no">No</th><th>Nama</th><th>L/P</th><th>Tanggal lahir</th><th>No. KTA</th><th>No. HP</th><th>Mendampingi</th></tr></thead>
        <tbody>
            @forelse ($pembina as $member)
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->gender?->value ?? '-' }}</td>
                    <td>{{ $birth($member) }}</td>
                    <td>{{ $v($member->kta_number) }}</td>
                    <td>{{ $v($member->phone) }}</td>
                    <td>{{ $member->accompanies?->label() ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Belum ada pembina.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="dates">Dikirim {{ $submittedAt }} · Diverifikasi {{ $verifiedAt }}</p>
</body>
</html>
