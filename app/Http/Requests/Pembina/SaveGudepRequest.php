<?php

namespace App\Http\Requests\Pembina;

use App\Models\Gudep;
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

        return $gudep === null ? $user->can('create', Gudep::class) : $user->can('update', $gudep);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gudep_number' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z.\-\/ ]+$/'],
            'npsn' => ['required', 'digits:8'],
            'institution' => ['required', 'string', 'max:150'],
            'pic_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'gudep_number' => 'no gudep',
            'npsn' => 'NPSN',
            'institution' => 'nama instansi',
            'pic_name' => 'penanggung jawab',
            'address' => 'alamat',
        ];
    }
}
