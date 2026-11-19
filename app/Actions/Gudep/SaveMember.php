<?php

namespace App\Actions\Gudep;

use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use App\Services\Files\PrivateFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tambah atau ubah anggota gudep (FR-GDP-02..04). Kuota tiap regu dijaga di
 * dalam transaksi dengan mengunci baris gudep, sehingga peserta ke-9 selalu
 * ditolak walau dua permintaan datang bersamaan (BR-02).
 */
class SaveMember
{
    public function __construct(private readonly PrivateFileService $files) {}

    /**
     * @param  array<string, mixed>  $data  sudah tervalidasi
     */
    public function handle(Gudep $gudep, ?Member $member, array $data, ?UploadedFile $photo = null): Member
    {
        $newPhotoPath = $photo ? $this->files->store($photo, "photos/{$gudep->id}") : null;

        try {
            return DB::transaction(function () use ($gudep, $member, $data, $newPhotoPath) {
                Gudep::query()->whereKey($gudep->id)->lockForUpdate()->first();

                $member ??= $this->newMember($gudep, (string) $data['regu']);
                $oldPhotoPath = $member->photo_path;

                $member->fill([
                    'name' => $data['name'],
                    'birth_date' => $data['birth_date'] ?? null,
                    'kta_number' => $data['kta_number'] ?? null,
                ]);

                if ($member->isPembina()) {
                    $member->fill([
                        'gender' => $data['gender'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'accompanies' => $data['accompanies'] ?? null,
                    ]);
                } else {
                    // Jenis kelamin peserta mengikuti regunya (desain M3b: terkunci).
                    $member->gender = $member->group === TeamGroup::Pi ? Gender::P : Gender::L;
                }

                if ($newPhotoPath !== null) {
                    $member->photo_path = $newPhotoPath;
                }

                $member->save();

                if ($newPhotoPath !== null && $oldPhotoPath !== null) {
                    DB::afterCommit(fn () => $this->files->delete($oldPhotoPath));
                }

                return $member;
            });
        } catch (\Throwable $exception) {
            $this->files->delete($newPhotoPath);

            throw $exception;
        }
    }

    private function newMember(Gudep $gudep, string $regu): Member
    {
        if ($regu === 'pembina') {
            return $gudep->members()->make(['role' => MemberRole::Pembina]);
        }

        $group = TeamGroup::from($regu);
        $max = (int) config('camp.peserta_per_regu');

        if ($gudep->peserta($group)->count() >= $max) {
            throw ValidationException::withMessages([
                'regu' => "Regu {$group->label()} sudah penuh ({$max}/{$max}). Peserta ke-".($max + 1).' tidak dapat ditambahkan.',
            ]);
        }

        return $gudep->members()->make([
            'role' => MemberRole::Peserta,
            'group' => $group,
        ]);
    }
}
