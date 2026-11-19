<?php

namespace App\Actions\Gudep;

use App\Enums\MemberRole;
use App\Models\Gudep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-GDP-01: simpan data gudep sebagai draft. Saat pertama dibuat, pemilik
 * akun otomatis menjadi pembina pendamping pertama (BR-03).
 */
class SaveGudep
{
    /**
     * @param  array{name: string, gudep_number: string, institution: string, region: string}  $data
     */
    public function handle(User $user, array $data): Gudep
    {
        return DB::transaction(function () use ($user, $data) {
            $gudep = $user->gudep()->firstOrNew();
            $gudep->fill($data);
            $isNew = ! $gudep->exists;
            $gudep->save();

            if ($isNew) {
                $gudep->members()->create([
                    'role' => MemberRole::Pembina,
                    'name' => $user->name,
                    'is_owner' => true,
                ]);
            }

            // Perbarui relasi yang mungkin sudah dimuat sebagai null.
            $user->setRelation('gudep', $gudep);

            return $gudep;
        });
    }
}
