<?php

namespace App\Models;

use App\Enums\MemberRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Jenis dokumen persyaratan (BR-05). `scope` member = per orang,
 * gudep = satu untuk seluruh gudep.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string $short_label
 * @property string $scope
 * @property list<string>|null $required_for
 * @property int $sort
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['code', 'label', 'short_label', 'scope', 'required_for', 'sort'])]
class DocumentType extends Model
{
    protected function casts(): array
    {
        return [
            'required_for' => 'array',
        ];
    }

    public function isGudepScope(): bool
    {
        return $this->scope === 'gudep';
    }

    public function requiredFor(MemberRole $role): bool
    {
        return ! $this->isGudepScope() && in_array($role->value, $this->required_for ?? [], true);
    }
}
