<?php

namespace App\Http\Requests\Admin;

use App\Enums\TemplateTarget;
use App\Models\Template;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi data template dan daftar field (FR-TPL-02, FR-TPL-05). */
class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Template $template */
        $template = $this->route('template');
        $hex = 'regex:/^#[0-9A-Fa-f]{6}$/';

        return [
            'name' => ['required', 'string', 'max:120'],
            'applies_to' => ['required', Rule::enum(TemplateTarget::class)],
            'fields' => ['present', 'array', 'max:40'],
            'fields.*.id' => ['required', 'string', 'max:64'],
            'fields.*.kind' => ['required', Rule::in(['text', 'photo', 'qr'])],
            'fields.*.label' => ['required', 'string', 'max:60'],
            'fields.*.enabled' => ['required', 'boolean'],
            'fields.*.x' => ['required', 'numeric', 'min:-50', 'max:'.$template->width_mm],
            'fields.*.y' => ['required', 'numeric', 'min:-50', 'max:'.$template->height_mm],
            'fields.*.w' => ['required', 'numeric', 'min:1', 'max:'.($template->width_mm + 50)],
            'fields.*.h' => ['required', 'numeric', 'min:1', 'max:'.($template->height_mm + 50)],
            'fields.*.content' => ['nullable', 'string', 'max:500'],
            'fields.*.font' => ['nullable', Rule::in(['display', 'sans', 'mono'])],
            'fields.*.weight' => ['nullable', 'integer', Rule::in([400, 500, 600, 700, 800])],
            'fields.*.italic' => ['nullable', 'boolean'],
            'fields.*.size' => ['nullable', 'numeric', 'min:3', 'max:120'],
            'fields.*.color' => ['nullable', $hex],
            'fields.*.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'fields.*.uppercase' => ['nullable', 'boolean'],
            'fields.*.letter_spacing' => ['nullable', 'numeric', 'min:-0.1', 'max:1'],
            'fields.*.bg' => ['nullable', $hex],
            'fields.*.radius' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'fields.*.border' => ['nullable', $hex],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nama template', 'applies_to' => 'sasaran'];
    }
}
