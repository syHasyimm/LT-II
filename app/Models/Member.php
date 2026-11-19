<?php

namespace App\Models;

use App\Enums\Accompanies;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Anggota gudep: peserta (regu Pa/Pi) atau pembina pendamping. Peserta bukan
 * pengguna sistem, hanya data yang dikelola pembina.
 *
 * @property int $id
 * @property int $gudep_id
 * @property MemberRole $role
 * @property TeamGroup|null $group
 * @property string $name
 * @property Gender|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $kta_number
 * @property string|null $phone
 * @property Accompanies|null $accompanies
 * @property string|null $photo_path
 * @property bool $is_owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Gudep $gudep
 */
#[Fillable(['role', 'group', 'name', 'gender', 'birth_date', 'kta_number', 'phone', 'accompanies', 'photo_path', 'is_owner'])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'group' => TeamGroup::class,
            'gender' => Gender::class,
            'accompanies' => Accompanies::class,
            'birth_date' => 'date',
            'is_owner' => 'boolean',
        ];
    }

    /** @return BelongsTo<Gudep, $this> */
    public function gudep(): BelongsTo
    {
        return $this->belongsTo(Gudep::class);
    }

    public function isPembina(): bool
    {
        return $this->role === MemberRole::Pembina;
    }
}
