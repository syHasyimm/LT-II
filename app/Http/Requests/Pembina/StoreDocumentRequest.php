<?php

namespace App\Http\Requests\Pembina;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** FR-DOC-01: PDF/JPG/PNG maksimal 2 MB. Tipe MIME diperiksa dari isi file. */
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->gudep !== null && $user->can('update', $user->gudep);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', (array) config('camp.allowed_mimes')),
                'max:'.config('camp.max_upload_kb'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Format dokumen harus PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran dokumen maksimal '.round(config('camp.max_upload_kb') / 1024).' MB.',
            'file.required' => 'Pilih file dokumen terlebih dahulu.',
        ];
    }
}
