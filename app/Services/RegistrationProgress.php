<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * Kelengkapan pendaftaran satu gudep: dipakai Beranda Pembina (desain M2)
 * dan pemeriksaan sebelum kirim (BR-07, FR-VER-01).
 */
class RegistrationProgress
{
    /** @var Collection<int, Member> */
    private Collection $members;

    public function __construct(private readonly ?Gudep $gudep)
    {
        $this->members = $gudep?->members()->get() ?? collect();
    }

    public static function for(?Gudep $gudep): self
    {
        return new self($gudep);
    }

    public function perRegu(): int
    {
        return (int) config('camp.peserta_per_regu');
    }

    public function countPeserta(TeamGroup $group): int
    {
        return $this->members->where('group', $group)->count();
    }

    /** @return Collection<int, Member> */
    public function pembina(): Collection
    {
        return $this->members->filter(fn (Member $member) => $member->isPembina())->values();
    }

    public function completeMembers(): int
    {
        return $this->members->filter(fn (Member $member) => $member->isComplete())->count();
    }

    /**
     * Daftar kekurangan yang menghalangi pengiriman (BR-07).
     *
     * @return list<string>
     */
    public function issues(): array
    {
        if ($this->gudep === null) {
            return ['Data gudep belum diisi.'];
        }

        $issues = [];
        $max = $this->perRegu();

        foreach (TeamGroup::cases() as $group) {
            $count = $this->countPeserta($group);

            if ($count !== $max) {
                $issues[] = "Regu {$group->label()} baru {$count}/{$max} peserta.";
            }
        }

        if ($this->pembina()->count() < (int) config('camp.pembina_min')) {
            $issues[] = 'Minimal '.config('camp.pembina_min').' pembina pendamping.';
        }

        foreach ($this->members as $member) {
            $missing = $member->missingData();

            if ($missing !== []) {
                $issues[] = "{$member->name}: ".implode(', ', $missing).' belum lengkap.';
            }
        }

        return [...$issues, ...$this->checklist()->issues()];
    }

    /**
     * Daftar periksa sebelum kirim (desain M5). Setiap butir punya tautan
     * perbaikan bila belum terpenuhi.
     *
     * @return list<array{key: string, label: string, detail: string|null, ok: bool, action: string|null, href: string|null}>
     */
    public function checklistItems(): array
    {
        if ($this->gudep === null) {
            return [[
                'key' => 'gudep', 'label' => 'Data gudep belum diisi', 'detail' => null,
                'ok' => false, 'action' => 'Isi', 'href' => route('pembina.gudep.edit'),
            ]];
        }

        $max = $this->perRegu();
        $items = [[
            'key' => 'gudep', 'label' => 'Data gudep lengkap', 'detail' => null,
            'ok' => true, 'action' => null, 'href' => null,
        ]];

        foreach (TeamGroup::cases() as $group) {
            $count = $this->countPeserta($group);
            $ok = $count === $max;
            $items[] = [
                'key' => "regu_{$group->value}",
                'label' => "Regu {$group->label()} {$count}/{$max}",
                'detail' => $ok ? null : ($count < $max ? 'Tambah '.($max - $count).' peserta lagi' : 'Kurangi '.($count - $max).' peserta'),
                'ok' => $ok,
                'action' => $ok ? null : 'Perbaiki',
                'href' => $ok ? null : route('pembina.members.index', ['tab' => $group->value]),
            ];
        }

        $pembinaCount = $this->pembina()->count();
        $pembinaOk = $pembinaCount >= (int) config('camp.pembina_min');
        $items[] = [
            'key' => 'pembina',
            'label' => $pembinaOk ? "{$pembinaCount} pembina pendamping" : 'Belum ada pembina pendamping',
            'detail' => $pembinaOk ? null : 'Minimal '.config('camp.pembina_min').' pembina',
            'ok' => $pembinaOk,
            'action' => $pembinaOk ? null : 'Tambah',
            'href' => $pembinaOk ? null : route('pembina.members.index', ['tab' => 'pembina']),
        ];

        $incomplete = $this->members->filter(fn (Member $member) => in_array('data diri', $member->missingData(), true) || in_array('kontak pembina', $member->missingData(), true));
        $items[] = [
            'key' => 'data',
            'label' => $incomplete->isEmpty() ? 'Data diri anggota lengkap' : "{$incomplete->count()} anggota belum lengkap datanya",
            'detail' => $incomplete->isEmpty() ? null : self::names($incomplete),
            'ok' => $incomplete->isEmpty(),
            'action' => $incomplete->isEmpty() ? null : 'Lengkapi',
            'href' => $incomplete->isEmpty() ? null : route('pembina.members.edit', $incomplete->first()),
        ];

        $noPhoto = $this->members->filter(fn (Member $member) => blank($member->photo_path));
        $items[] = [
            'key' => 'foto',
            'label' => $noPhoto->isEmpty() ? 'Semua pas foto diunggah' : "{$noPhoto->count()} pas foto belum diunggah",
            'detail' => $noPhoto->isEmpty() ? null : self::names($noPhoto),
            'ok' => $noPhoto->isEmpty(),
            'action' => $noPhoto->isEmpty() ? null : 'Unggah',
            'href' => $noPhoto->isEmpty() ? null : route('pembina.documents.index'),
        ];

        foreach ($this->checklist()->gudepTypes() as $type) {
            $document = $this->checklist()->documentFor(null, $type);
            $ok = $document !== null && $document->status !== DocumentStatus::Rejected;
            $items[] = [
                'key' => "gudep_{$type->code}",
                'label' => $type->label,
                'detail' => $ok ? null : ($document ? 'Ditolak, unggah ulang' : 'Belum diunggah'),
                'ok' => $ok,
                'action' => $ok ? null : 'Unggah',
                'href' => $ok ? null : route('pembina.documents.index'),
            ];
        }

        return $items;
    }

    /** @param Collection<int, Member> $members */
    private static function names(Collection $members): string
    {
        $names = $members->take(3)->pluck('name')->implode(', ');

        return $members->count() > 3 ? $names.' dan '.($members->count() - 3).' lainnya' : $names;
    }

    private ?DocumentChecklist $checklist = null;

    private function checklist(): DocumentChecklist
    {
        /** @var Gudep $gudep */
        $gudep = $this->gudep;

        return $this->checklist ??= DocumentChecklist::for($gudep);
    }

    /**
     * Berkas wajib (pas foto + dokumen) yang sudah diunggah dan totalnya.
     *
     * @return array{done: int, total: int}
     */
    public function files(): array
    {
        if ($this->gudep === null) {
            return ['done' => 0, 'total' => 0];
        }

        $counts = $this->checklist()->counts();
        $photos = $this->members->filter(fn (Member $member) => filled($member->photo_path))->count();

        return [
            'done' => $photos + $counts['uploaded'],
            'total' => $this->members->count() + $counts['total'],
        ];
    }

    /**
     * Langkah alur pendaftaran (Beranda dan timeline pembina): gudep, peserta_pa,
     * peserta_pi, pembina, berkas, kirim.
     *
     * @return list<array{key: string, title: string, subtitle: string, value: string, state: 'done'|'current'|'todo'|'warning', href: string}>
     */
    public function steps(): array
    {
        $max = $this->perRegu();
        $pa = $this->countPeserta(TeamGroup::Pa);
        $pi = $this->countPeserta(TeamGroup::Pi);
        $pembina = $this->pembina();
        $pembinaComplete = $pembina->filter(fn (Member $member) => $member->isComplete())->count();
        $members = $this->members->count();
        $complete = $this->completeMembers();
        $files = $this->files();
        $hasGudep = $this->gudep !== null;
        $status = $this->gudep?->status;

        $steps = [
            [
                'key' => 'gudep',
                'title' => 'Data gudep',
                'subtitle' => 'NPSN, instansi, penanggung jawab, alamat',
                'value' => $hasGudep ? 'Lengkap' : 'Belum',
                'done' => $hasGudep,
                'href' => route('pembina.gudep.edit'),
            ],
            [
                'key' => 'peserta_pa',
                'title' => 'Peserta Putra',
                'subtitle' => "Regu Putra · {$pa}/{$max}",
                'value' => "{$pa}/{$max}",
                'done' => $pa === $max,
                'href' => route('pembina.members.index', ['tab' => 'pa']),
            ],
            [
                'key' => 'peserta_pi',
                'title' => 'Peserta Putri',
                'subtitle' => "Regu Putri · {$pi}/{$max}",
                'value' => "{$pi}/{$max}",
                'done' => $pi === $max,
                'href' => route('pembina.members.index', ['tab' => 'pi']),
            ],
            [
                'key' => 'pembina',
                'title' => 'Pembina pendamping',
                'subtitle' => 'Minimal '.config('camp.pembina_min').', tanpa batas',
                'value' => $pembina->count().' pembina',
                'done' => $pembina->count() >= (int) config('camp.pembina_min') && $pembinaComplete === $pembina->count(),
                'href' => route('pembina.members.index', ['tab' => 'pembina']),
            ],
            [
                'key' => 'berkas',
                'title' => 'Foto & dokumen',
                'subtitle' => 'Pas foto, KTA, izin ortu, kesehatan',
                'value' => "{$files['done']}/{$files['total']}",
                'done' => $files['total'] > 0 && $files['done'] === $files['total'] && $complete === $members,
                'href' => route('pembina.documents.index'),
            ],
            [
                'key' => 'kirim',
                'title' => 'Kirim pendaftaran',
                'subtitle' => 'Data terkunci setelah dikirim',
                'value' => $status?->label() ?? 'Belum',
                'done' => in_array($status, [GudepStatus::Submitted, GudepStatus::Verified], true),
                'href' => route('pembina.submission.show'),
            ],
        ];

        $currentFound = false;

        return array_map(function (array $step) use (&$currentFound) {
            $state = 'todo';

            if ($step['done']) {
                $state = 'done';
            } elseif (! $currentFound) {
                $state = 'current';
                $currentFound = true;
            }

            unset($step['done']);

            return [...$step, 'state' => $state];
        }, $steps);
    }

    /** Persentase kelengkapan (langkah 1–4, tanpa langkah kirim). */
    public function percent(): int
    {
        if ($this->gudep === null) {
            return 0;
        }

        $max = $this->perRegu();
        $peserta = min($max, $this->countPeserta(TeamGroup::Pa)) + min($max, $this->countPeserta(TeamGroup::Pi));
        $files = $this->files();
        $pembinaOk = $this->pembina()->count() >= (int) config('camp.pembina_min') ? 1 : 0;

        $parts = [
            1.0,
            $peserta / ($max * 2),
            $pembinaOk,
            $files['total'] > 0 ? $files['done'] / $files['total'] : 0,
        ];

        return (int) round(array_sum($parts) / count($parts) * 100);
    }
}
