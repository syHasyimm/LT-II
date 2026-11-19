<?php

namespace App\Services\Pdf;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Services\Files\PrivateFileService;

/** Data satu penerima ID Card / Sertifikat untuk dirender ke template. */
final readonly class RenderSubject
{
    /**
     * @param  array<string, string>  $values  nilai placeholder, misalnya ['nama' => '…']
     */
    public function __construct(
        public array $values,
        public ?string $photoDataUri,
        public string $qrContent,
    ) {}

    public static function fromMember(Member $member, string $serial): self
    {
        $gudep = $member->gudep;
        $isPembina = $member->role === MemberRole::Pembina;
        $group = $member->group?->label();

        return new self(
            values: [
                'nama' => $member->name,
                'peran' => $isPembina ? 'Pembina Pendamping' : "Peserta · Regu {$group}",
                'peran_lengkap' => $isPembina ? 'Pembina Pendamping' : "Peserta Regu {$group}",
                'gudep' => $gudep->name,
                'sekolah' => $gudep->institution,
                'kwartir' => $gudep->region,
                'nomor_seri' => $serial,
                'tahun' => (string) config('camp.event_year'),
            ],
            photoDataUri: self::photo($member),
            qrContent: route('verify.show', $serial),
        );
    }

    /** Data contoh untuk pratinjau template tanpa anggota. */
    public static function sample(string $serial = 'LT2-2026-ID-0021'): self
    {
        return new self(
            values: [
                'nama' => 'Aisyah Putri Ramadhani',
                'peran' => 'Peserta · Regu Putri',
                'peran_lengkap' => 'Peserta Regu Putri',
                'gudep' => 'Gudep 07.041',
                'sekolah' => 'SMPN 3 Sleman',
                'kwartir' => 'Kwarcab Sleman',
                'nomor_seri' => $serial,
                'tahun' => (string) config('camp.event_year'),
            ],
            photoDataUri: null,
            qrContent: route('verify.show', $serial),
        );
    }

    private static function photo(Member $member): ?string
    {
        $files = app(PrivateFileService::class);

        if (! $files->exists($member->photo_path)) {
            return null;
        }

        /** @var string $path */
        $path = $member->photo_path;
        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return "data:{$mime};base64,".base64_encode($files->get($path));
    }

    public function interpolate(string $content): string
    {
        return (string) preg_replace_callback('/\{([a-z_]+)\}/', fn ($match) => $this->values[$match[1]] ?? $match[0], $content);
    }
}
