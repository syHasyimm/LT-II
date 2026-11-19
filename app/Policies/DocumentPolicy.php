<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/** FR-DOC-04: dokumen hanya untuk admin dan pembina pemilik gudep. */
class DocumentPolicy
{
    public function __construct(private readonly GudepPolicy $gudeps) {}

    public function view(User $user, Document $document): bool
    {
        return $this->gudeps->view($user, $document->gudep);
    }

    public function review(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }
}
