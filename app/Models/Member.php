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
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isPembina(): bool
    {
        return $this->role === MemberRole::Pembina;
    }

    /**
     * Data wajib anggota sudah terisi (FR-GDP-04, BR-06). Dokumen diperiksa
     * terpisah lewat matriks kelengkapan.
     *
     * @return list<string> daftar data yang belum lengkap
     */
    public function missingData(): array
    {
        $missing = [];

        if ($this->gender === null || $this->birth_date === null || blank($this->kta_number)) {
            $missing[] = 'data diri';
        }

        if ($this->isPembina() && (blank($this->phone) || $this->accompanies === null)) {
            $missing[] = 'kontak pembina';
        }

        if (blank($this->photo_path)) {
            $missing[] = 'pas foto';
        }

        return $missing;
    }

    public function isComplete(): bool
    {
        return $this->missingData() === [];
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }
}
