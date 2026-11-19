<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'group' => $this->group?->value,
            'name' => $this->name,
            'gender' => $this->gender?->value,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age(),
            'kta_number' => $this->kta_number,
            'phone' => $this->phone,
            'accompanies' => $this->accompanies?->value,
            'accompanies_label' => $this->accompanies?->label(),
            'is_owner' => $this->is_owner,
            'photo_url' => $this->photo_path
                ? route('members.photo', ['member' => $this->id, 'v' => substr(md5($this->photo_path), 0, 8)])
                : null,
            'missing' => $this->missingData(),
        ];
    }
}
