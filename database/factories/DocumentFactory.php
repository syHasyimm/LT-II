<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gudep_id' => Gudep::factory(),
            'member_id' => null,
            'document_type_id' => fn () => DocumentType::query()->where('code', 'kesiapan_sekolah')->value('id'),
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => 'dokumen.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(50_000, 900_000),
            'status' => DocumentStatus::Pending,
        ];
    }

    public function type(string $code): static
    {
        return $this->state(fn () => [
            'document_type_id' => DocumentType::query()->where('code', $code)->value('id'),
        ]);
    }

    public function status(DocumentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
