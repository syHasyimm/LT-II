<?php

namespace App\Http\Requests\Pembina;

use App\Enums\Accompanies;
use App\Enums\Gender;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi data anggota (FR-GDP-04). Dipakai untuk tambah dan ubah. */
class SaveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();
        $member = $this->member();

        if ($member !== null) {
            return $user->can('update', $member);
        }

        return $user->gudep !== null && $user->can('update', $user->gudep);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPembina = $this->member()?->isPembina() ?? $this->input('regu') === 'pembina';

        return [
            'regu' => [Rule::requiredIf($this->member() === null), Rule::in(['pa', 'pi', 'pembina'])],
            'name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1940-01-01'],
            'kta_number' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z.\-\/ ]+$/'],
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', (array) config('camp.photo_mimes')),
                'max:'.config('camp.max_upload_kb'),
            ],
            'gender' => [Rule::requiredIf($isPembina), 'nullable', Rule::enum(Gender::class)],
            'phone' => [Rule::requiredIf($isPembina), 'nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9\- ]{7,14}$/'],
            'accompanies' => [Rule::requiredIf($isPembina), 'nullable', Rule::enum(Accompanies::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'birth_date' => 'tanggal lahir',
            'kta_number' => 'nomor KTA',
            'photo' => 'pas foto',
            'gender' => 'jenis kelamin',
            'phone' => 'nomor HP',
            'accompanies' => 'keterangan mendampingi',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP harus nomor ponsel Indonesia, misalnya 0812-3456-7890.',
            'photo.max' => 'Ukuran pas foto maksimal '.round(config('camp.max_upload_kb') / 1024).' MB.',
        ];
    }

    public function member(): ?Member
    {
        $member = $this->route('member');

        return $member instanceof Member ? $member : null;
    }
}
