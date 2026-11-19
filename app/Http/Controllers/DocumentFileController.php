<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Files\PrivateFileService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tampilkan dokumen dari disk privat setelah memeriksa otorisasi (FR-DOC-04, FR-DOC-05). */
class DocumentFileController extends Controller
{
    public function __invoke(Document $document, PrivateFileService $files): StreamedResponse
    {
        Gate::authorize('view', $document);

        abort_unless($files->exists($document->path), 404);

        return $files->inline($document->path, $document->original_name);
    }
}
