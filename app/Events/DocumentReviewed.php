<?php

namespace App\Events;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Admin menyetujui atau menolak dokumen (dicatat di audit log, FR-VER-06). */
class DocumentReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Document $document,
        public User $by,
    ) {}
}
