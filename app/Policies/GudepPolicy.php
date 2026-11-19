<?php

namespace App\Policies;

use App\Models\Gudep;
use App\Models\User;
use App\Services\RegistrationWindow;

/**
 * FR-AUTH-04: pembina hanya melihat dan mengelola gudepnya sendiri. Admin
 * dapat melihat semua gudep.
 */
class GudepPolicy
{
    public function view(User $user, Gudep $gudep): bool
    {
        return $user->isAdmin() || $this->owns($user, $gudep);
    }

    /**
     * Mengubah data gudep dan anggotanya: status draft/revisi (FR-GDP-05)
     * dan masih dalam jadwal atau perpanjangan (FR-PRD-02).
     */
    public function update(User $user, Gudep $gudep): bool
    {
        return $this->owns($user, $gudep)
            && $gudep->status->isEditable()
            && app(RegistrationWindow::class)->allowsChangesFor($gudep);
    }

    /** Membuat gudep baru hanya saat pendaftaran dibuka (BR-12). */
    public function create(User $user): bool
    {
        return $user->isPembina()
            && $user->gudep === null
            && app(RegistrationWindow::class)->isOpen();
    }

    public function verify(User $user, Gudep $gudep): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, Gudep $gudep): bool
    {
        return $user->isPembina() && $gudep->user_id === $user->id;
    }
}
