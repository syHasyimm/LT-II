<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\Files\PrivateFileService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Pas foto anggota dari disk privat, hanya untuk pembina pemilik dan admin. */
class MemberPhotoController extends Controller
{
    public function __invoke(Member $member, PrivateFileService $files): StreamedResponse
    {
        Gate::authorize('view', $member);

        abort_unless($files->exists($member->photo_path), 404);

        /** @var string $path */
        $path = $member->photo_path;

        return $files->inline($path, 'pas-foto-'.Str::slug($member->name).'.'.pathinfo($path, PATHINFO_EXTENSION));
    }
}
