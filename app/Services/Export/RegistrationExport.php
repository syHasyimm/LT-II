<?php

namespace App\Services\Export;

use App\Enums\DocumentStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Enums\TemplateType;
use App\Models\DocumentType;
use App\Models\GeneratedFile;
use App\Models\Gudep;
use App\Models\Member;
use App\Services\DocumentChecklist;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Shuchkin\SimpleXLSXGen;

/**
 * Ekspor rekap pendaftaran ke Excel (FR-DSH-02): sheet "Gudep" (satu baris per
 * gudep) dan sheet "Anggota" (nama, gudep, peran, status tiap dokumen).
 *
 * Semua isi dari pengguna ditulis sebagai teks mentah, sehingga tidak bisa
 * berubah menjadi rumus atau format (mencegah formula injection).
 */
class RegistrationExport
{
    private const HEADER_STYLE = '<style bgcolor="#7A1219" color="#FFFFFF"><b>%s</b></style>';

    /**
     * @param  Builder<Gudep>  $gudeps
     */
    public function __construct(private readonly Builder $gudeps) {}

    /**
     * @param  Builder<Gudep>  $gudeps
     */
    public static function for(Builder $gudeps): self
    {
        return new self($gudeps);
    }

    public function filename(): string
    {
        return 'rekap-pendaftaran-'.config('camp.serial_prefix').'-'.CarbonImmutable::now(config('camp.timezone'))->format('Y-m-d-His').'.xlsx';
    }

    public function workbook(): SimpleXLSXGen
    {
        $gudeps = $this->gudeps->with(['user', 'members' => fn ($query) => $query->orderByRaw("case role when 'peserta' then 0 else 1 end")->orderBy('group')->orderBy('id')])->get();

        /** @var Collection<string, string> $serials kunci: "{member_id}:{type}" */
        $serials = GeneratedFile::query()
            ->whereIn('member_id', $gudeps->flatMap->members->pluck('id'))
            ->get(['member_id', 'type', 'serial_number'])
            ->mapWithKeys(fn (GeneratedFile $file) => ["{$file->member_id}:{$file->type->value}" => $file->serial_number]);

        $gudepRows = [$this->header([
            'No', 'Gudep', 'No. Gudep', 'Sekolah / Pangkalan', 'Kwartir Ranting', 'Pemilik akun', 'Email pemilik', 'Status',
            'Peserta Pa', 'Peserta Pi', 'Pembina', 'Dokumen wajib', 'Diunggah', 'Disetujui', 'Ditolak', 'Belum diunggah',
            'Dikirim', 'Diverifikasi', 'Catatan admin',
        ])];

        $types = DocumentType::query()->orderBy('sort')->get()->reject->isGudepScope()->values();

        $memberRows = [$this->header([
            'No', 'Nama', 'Peran', 'Regu / Mendampingi', 'Jenis kelamin', 'Tanggal lahir', 'Nomor KTA', 'No. HP',
            'Gudep', 'No. Gudep', 'Sekolah / Pangkalan', 'Kwartir Ranting', 'Status gudep',
            ...$types->map(fn (DocumentType $type) => $type->short_label ?: $type->label)->all(),
            'Status dokumen', 'Nomor ID Card', 'Nomor Sertifikat',
        ])];

        foreach ($gudeps->values() as $index => $gudep) {
            $checklist = DocumentChecklist::for($gudep);
            $counts = $checklist->counts();

            $gudepRows[] = [
                $index + 1,
                $this->text($gudep->name),
                $this->text($gudep->gudep_number),
                $this->text($gudep->institution),
                $this->text($gudep->region),
                $this->text($gudep->user->name),
                $this->text($gudep->user->email),
                $gudep->status->label(),
                $gudep->members->where('role', MemberRole::Peserta)->where('group', TeamGroup::Pa)->count(),
                $gudep->members->where('role', MemberRole::Peserta)->where('group', TeamGroup::Pi)->count(),
                $gudep->members->where('role', MemberRole::Pembina)->count(),
                $counts['total'],
                $counts['uploaded'],
                $counts['approved'],
                $counts['rejected'],
                $counts['missing'],
                $this->datetime($gudep->submitted_at),
                $this->datetime($gudep->verified_at),
                $this->text($gudep->admin_note),
            ];

            foreach ($gudep->members as $member) {
                $required = $types->filter(fn (DocumentType $type) => $type->requiredFor($member->role));
                $statuses = $required->map(fn (DocumentType $type) => $checklist->documentFor($member, $type)?->status)->values()->all();

                $memberRows[] = [
                    count($memberRows),
                    $this->text($member->name),
                    $member->role->label(),
                    $this->team($member),
                    $member->gender?->label() ?? '',
                    $member->birth_date?->format('Y-m-d') ?? '',
                    $this->text($member->kta_number),
                    $this->text($member->phone),
                    $this->text($gudep->name),
                    $this->text($gudep->gudep_number),
                    $this->text($gudep->institution),
                    $this->text($gudep->region),
                    $gudep->status->label(),
                    ...$types->map(function (DocumentType $type) use ($member, $checklist) {
                        if (! $type->requiredFor($member->role)) {
                            return 'Tidak wajib';
                        }

                        return $checklist->documentFor($member, $type)?->status->label() ?? 'Belum diunggah';
                    })->all(),
                    $this->summary($statuses),
                    $serials->get("{$member->id}:".TemplateType::IdCard->value, ''),
                    $serials->get("{$member->id}:".TemplateType::Certificate->value, ''),
                ];
            }
        }

        $xlsx = SimpleXLSXGen::create('Rekap Pendaftaran Perkemahan LT 2')
            ->setDefaultFont('Calibri')
            ->setDefaultFontSize(11);

        $xlsx->addSheet($gudepRows, 'Gudep');
        $this->decorate($xlsx, count($gudepRows[0]), count($gudepRows), [5, 28, 12, 30, 22, 24, 30, 14, 11, 11, 10, 14, 11, 11, 10, 15, 18, 18, 40]);

        $xlsx->addSheet($memberRows, 'Anggota');
        $widths = array_merge([5, 30, 20, 20, 14, 14, 18, 16, 26, 12, 30, 22, 14], array_fill(0, $types->count(), 16), [24, 22, 22]);
        $this->decorate($xlsx, count($memberRows[0]), count($memberRows), $widths);

        return $xlsx;
    }

    /**
     * @param  list<int>  $widths
     */
    private function decorate(SimpleXLSXGen $xlsx, int $columns, int $rows, array $widths): void
    {
        $last = SimpleXLSXGen::coord2cell($columns - 1);
        $xlsx->freezePanes('A2')->autoFilter("A1:{$last}".max($rows, 1));

        foreach (array_slice($widths, 0, $columns) as $column => $width) {
            $xlsx->setColWidth($column + 1, $width);
        }
    }

    /**
     * @param  list<string>  $labels
     * @return list<string>
     */
    private function header(array $labels): array
    {
        return array_map(fn (string $label) => sprintf(self::HEADER_STYLE, SimpleXLSXGen::esc($label)), $labels);
    }

    private function text(?string $value): string
    {
        return $value === null || $value === '' ? '' : SimpleXLSXGen::raw($value);
    }

    private function datetime(?CarbonImmutable $value): string
    {
        return $value?->setTimezone(config('camp.timezone'))->format('Y-m-d H:i') ?? '';
    }

    private function team(Member $member): string
    {
        if ($member->role === MemberRole::Peserta) {
            return $member->group?->label() ?? '';
        }

        return $member->accompanies !== null ? 'Mendampingi '.mb_strtolower($member->accompanies->label()) : '';
    }

    /**
     * @param  array<int, DocumentStatus|null>  $statuses
     */
    private function summary(array $statuses): string
    {
        if ($statuses === []) {
            return 'Tidak ada dokumen wajib';
        }

        $approved = count(array_filter($statuses, fn (?DocumentStatus $status) => $status === DocumentStatus::Approved));
        $missing = count(array_filter($statuses, fn (?DocumentStatus $status) => $status === null));
        $rejected = count(array_filter($statuses, fn (?DocumentStatus $status) => $status === DocumentStatus::Rejected));

        if ($approved === count($statuses)) {
            return 'Semua disetujui';
        }

        return match (true) {
            $rejected > 0 => "{$rejected} ditolak",
            $missing > 0 => "{$missing} belum diunggah",
            default => 'Menunggu verifikasi',
        };
    }
}
