<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'original_name' => $this->original_name,
            'size_label' => $this->sizeLabel(),
            'mime' => $this->mime,
            'note' => $this->note,
            'uploaded_at' => $this->created_at?->translatedFormat('j M Y, H.i'),
            'url' => route('documents.file', $this->id),
        ];
    }
}
