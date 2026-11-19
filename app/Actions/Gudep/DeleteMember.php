<?php

namespace App\Actions\Gudep;

use App\Models\Member;
use App\Services\Files\PrivateFileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Hapus anggota beserta pas fotonya. Pembina pemilik akun tidak bisa dihapus (BR-03). */
class DeleteMember
{
    public function __construct(private readonly PrivateFileService $files) {}

    public function handle(Member $member): void
    {
        if ($member->is_owner) {
            throw ValidationException::withMessages([
                'member' => 'Pembina pemilik akun tidak dapat dihapus.',
            ]);
        }

        $photo = $member->photo_path;

        DB::transaction(fn () => $member->delete());

        $this->files->delete($photo);
    }
}
