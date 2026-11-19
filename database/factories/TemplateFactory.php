<?php

namespace Database\Factories;

use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Models\Template;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => TemplateType::IdCard,
            'applies_to' => TemplateTarget::All,
            'name' => 'Template '.fake()->word(),
            'background_path' => null,
            'width_mm' => 55,
            'height_mm' => 85,
            'fields' => [
                DefaultTemplates::text('Nama', '{nama}', x: 2, y: 40, w: 51, h: 5, size: 10),
                DefaultTemplates::qr('QR', x: 40, y: 70, size: 10),
            ],
            'is_active' => true,
        ];
    }

    public function certificate(): static
    {
        return $this->state(fn () => [
            'type' => TemplateType::Certificate,
            'width_mm' => 297,
            'height_mm' => 210,
        ]);
    }
}
