<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/** Akses data anggota mengikuti akses gudep pemiliknya (FR-AUTH-04, FR-GDP-05). */
class MemberPolicy
{
    public function __construct(private readonly GudepPolicy $gudeps) {}

    public function view(User $user, Member $member): bool
    {
        return $this->gudeps->view($user, $member->gudep);
    }

    public function update(User $user, Member $member): bool
    {
        return $this->gudeps->update($user, $member->gudep);
    }

    /** BR-03: pembina pemilik akun tidak dapat dihapus. */
    public function delete(User $user, Member $member): bool
    {
        return ! $member->is_owner && $this->update($user, $member);
    }
}
