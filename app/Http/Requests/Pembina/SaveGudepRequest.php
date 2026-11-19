<?php

namespace App\Http\Requests\Pembina;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveGudepRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();
        $gudep = $user->gudep;

        return $gudep === null ? $user->isPembina() : $user->can('update', $gudep);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'gudep_number' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z.\-\/ ]+$/'],
            'institution' => ['required', 'string', 'max:150'],
            'region' => ['required', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama gudep',
            'gudep_number' => 'nomor gudep',
            'institution' => 'asal sekolah/lembaga',
            'region' => 'kwartir/wilayah',
        ];
    }
}
