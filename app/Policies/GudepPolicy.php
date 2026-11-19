<?php

namespace App\Policies;

use App\Models\Gudep;
use App\Models\User;

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

    /** Mengubah data gudep dan anggotanya (status draft/revisi, FR-GDP-05). */
    public function update(User $user, Gudep $gudep): bool
    {
        return $this->owns($user, $gudep) && $gudep->status->isEditable();
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
