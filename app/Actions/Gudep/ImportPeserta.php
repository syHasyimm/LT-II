<?php

namespace App\Actions\Gudep;

use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Http\Requests\Pembina\SaveMemberRequest;
use App\Models\ActivityLog;
use App\Models\Gudep;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Impor peserta dari Excel/CSV (FR-GDP-06).
 *
 * Kolom dikenali dari judulnya (urutan bebas): Regu, Nama lengkap, Tanggal
 * lahir, Nomor KTA. Baris yang valid ditambahkan; baris yang tidak valid,
 * ganda, atau melebihi kuota 8 per regu (BR-02) dilaporkan beserta alasannya.
 * Pas foto tetap diunggah satu per satu setelah impor.
 */
class ImportPeserta
{
    public const MAX_ROWS = 100;

    private const COLUMNS = [
        'regu' => ['regu', 'reguparegupi', 'jeniskelamin', 'putraputri', 'kelompok'],
        'name' => ['nama', 'namalengkap', 'namapeserta'],
        'birth_date' => ['tanggallahir', 'tgllahir', 'lahir'],
        'kta_number' => ['nomorkta', 'nokta', 'kta', 'nomoranggota'],
    ];

    /**
     * @param  list<list<string>>  $rows  baris mentah dari SpreadsheetReader
     * @return array{added: int, skipped: list<array{row: int, name: string, reason: string}>}
     */
    public function handle(Gudep $gudep, array $rows): array
    {
        [$headerIndex, $columns] = $this->locateHeader($rows);

        $candidates = [];
        $skipped = [];

        foreach (array_slice($rows, $headerIndex + 1, null, true) as $index => $row) {
            $values = array_map(fn (int $column) => $row[$column] ?? '', $columns);

            if (implode('', $values) === '') {
                continue;
            }

            $candidates[] = ['row' => $index + 1, ...$values];
        }

        if ($candidates === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada data peserta di file. Isi mulai baris di bawah judul kolom.']);
        }

        if (count($candidates) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'File berisi lebih dari '.self::MAX_ROWS.' baris peserta. Periksa kembali isinya.']);
        }

        $max = (int) config('camp.peserta_per_regu');

        $added = DB::transaction(function () use ($gudep, $candidates, $max, &$skipped) {
            Gudep::query()->whereKey($gudep->id)->lockForUpdate()->first();

            $existing = $gudep->peserta()->get();
            $counts = [
                TeamGroup::Pa->value => $existing->where('group', TeamGroup::Pa)->count(),
                TeamGroup::Pi->value => $existing->where('group', TeamGroup::Pi)->count(),
            ];
            $seen = $existing->mapWithKeys(fn (Member $member) => [$this->identity($member->name, $member->birth_date?->format('Y-m-d')) => true])->all();
            $added = 0;

            foreach ($candidates as $candidate) {
                $name = Str::squish($candidate['name']);
                $group = $this->group($candidate['regu']);
                $birthDate = $this->date($candidate['birth_date']);

                $validator = Validator::make(
                    ['name' => $name, 'birth_date' => $birthDate ?? $candidate['birth_date'], 'kta_number' => $candidate['kta_number']],
                    SaveMemberRequest::identityRules(),
                    [],
                    SaveMemberRequest::attributeNames(),
                );

                $reason = match (true) {
                    $group === null => 'Regu harus diisi Putra atau Putri.',
                    $birthDate === null && trim($candidate['birth_date']) !== '' => 'Tanggal lahir tidak valid. Gunakan format hari/bulan/tahun, misalnya 15/03/2013.',
                    $validator->fails() => $validator->errors()->first(),
                    isset($seen[$this->identity($name, $birthDate)]) => 'Peserta dengan nama dan tanggal lahir yang sama sudah ada.',
                    $counts[$group->value] >= $max => "Regu {$group->label()} sudah penuh ({$max}/{$max}).",
                    default => null,
                };

                if ($reason !== null) {
                    $skipped[] = ['row' => $candidate['row'], 'name' => $name !== '' ? $name : '(tanpa nama)', 'reason' => $reason];

                    continue;
                }

                $gudep->members()->create([
                    'role' => MemberRole::Peserta,
                    'group' => $group,
                    'gender' => $group === TeamGroup::Pi ? Gender::P : Gender::L,
                    'name' => $name,
                    'birth_date' => $birthDate,
                    'kta_number' => trim($candidate['kta_number']),
                ]);

                $counts[$group->value]++;
                $seen[$this->identity($name, $birthDate)] = true;
                $added++;
            }

            if ($added > 0) {
                ActivityLog::record('members.imported', $gudep, ['added' => $added, 'skipped' => count($skipped)]);
            }

            return $added;
        });

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * Cari baris judul kolom di 10 baris pertama.
     *
     * @param  list<list<string>>  $rows
     * @return array{0: int, 1: array{regu: int, name: int, birth_date: int, kta_number: int}}
     */
    private function locateHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 10) as $index => $row) {
            $columns = [];

            foreach ($row as $column => $cell) {
                $key = preg_replace('/[^a-z]/', '', Str::lower(Str::ascii($cell))) ?? '';

                foreach (self::COLUMNS as $field => $aliases) {
                    if (! isset($columns[$field]) && $key !== '' && in_array($key, $aliases, true)) {
                        $columns[$field] = $column;
                    }
                }
            }

            if (isset($columns['regu'], $columns['name'], $columns['birth_date'], $columns['kta_number'])) {
                return [$index, [
                    'regu' => $columns['regu'],
                    'name' => $columns['name'],
                    'birth_date' => $columns['birth_date'],
                    'kta_number' => $columns['kta_number'],
                ]];
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Judul kolom tidak dikenali. Gunakan template impor: Regu, Nama lengkap, Tanggal lahir, Nomor KTA.',
        ]);
    }

    private function group(string $value): ?TeamGroup
    {
        return match (preg_replace('/[^a-z]/', '', Str::lower($value))) {
            'pa', 'putra', 'l', 'lakilaki', 'laki' => TeamGroup::Pa,
            'pi', 'putri', 'p', 'perempuan' => TeamGroup::Pi,
            default => null,
        };
    }

    /**
     * Tanggal dari Excel (sel tanggal atau angka seri) atau teks
     * berformat Indonesia (hari/bulan/tahun) maupun ISO.
     */
    private function date(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Angka seri tanggal Excel (sel tanggal yang terbaca sebagai angka).
        if (preg_match('/^\d{4,5}(\.0+)?$/', $value) === 1) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T].*)?$/', $value, $match) === 1) {
            [, $year, $month, $day] = $match;
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4}|\d{2})$/', $value, $match) === 1) {
            [, $day, $month, $year] = $match;

            if (strlen($year) === 2) {
                $year = ((int) $year > (int) date('y') ? '19' : '20').$year;
            }
        } else {
            return null;
        }

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function identity(string $name, ?string $birthDate): string
    {
        return Str::lower(Str::squish($name)).'|'.$birthDate;
    }
}
