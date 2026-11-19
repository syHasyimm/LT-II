<?php

namespace App\Services;

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

        return $issues;
    }

    /**
     * Langkah pendaftaran untuk Beranda Pembina.
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
        $hasGudep = $this->gudep !== null;
        $status = $this->gudep?->status;

        $steps = [
            [
                'key' => 'gudep',
                'title' => 'Data gudep',
                'subtitle' => 'Nama, nomor, sekolah, kwartir',
                'value' => $hasGudep ? 'Lengkap' : 'Belum',
                'done' => $hasGudep,
                'href' => route('pembina.gudep.edit'),
            ],
            [
                'key' => 'peserta',
                'title' => 'Peserta Pa & Pi',
                'subtitle' => "Pa {$pa}/{$max} · Pi {$pi}/{$max}",
                'value' => ($pa + $pi).'/'.($max * 2),
                'done' => $pa === $max && $pi === $max,
                'href' => route('pembina.members.index', ['tab' => $pa < $max ? 'pa' : 'pi']),
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
                'value' => "{$complete}/{$members}",
                'done' => $members > 0 && $complete === $members,
                'href' => route('pembina.members.index'),
            ],
            [
                'key' => 'kirim',
                'title' => 'Kirim pendaftaran',
                'subtitle' => 'Data terkunci setelah dikirim',
                'value' => $status?->label() ?? 'Belum',
                'done' => in_array($status, [GudepStatus::Submitted, GudepStatus::Verified], true),
                'href' => route('pembina.dashboard'),
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
        $members = max(1, $this->members->count());
        $pembinaOk = $this->pembina()->count() >= (int) config('camp.pembina_min') ? 1 : 0;

        $parts = [
            1.0,
            $peserta / ($max * 2),
            $pembinaOk,
            $this->completeMembers() / $members,
        ];

        return (int) round(array_sum($parts) / count($parts) * 100);
    }
}
