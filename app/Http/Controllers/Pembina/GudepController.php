<?php

namespace App\Http\Controllers\Pembina;

use App\Actions\Gudep\SaveGudep;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pembina\SaveGudepRequest;
use App\Models\Gudep;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GudepController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;

        return Inertia::render('pembina/gudep/form', [
            'gudep' => $gudep?->only(['gudep_number', 'npsn', 'institution', 'pic_name', 'address']),
            'editable' => $gudep === null ? $user->can('create', Gudep::class) : $user->can('update', $gudep),
        ]);
    }

    public function update(SaveGudepRequest $request, SaveGudep $saveGudep): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isNew = $user->gudep === null;

        /** @var array{gudep_number: string, npsn: string, institution: string, pic_name: string, address: string} $data */
        $data = $request->validated();
        $saveGudep->handle($user, $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isNew ? 'Data gudep tersimpan. Lanjutkan dengan mengisi anggota.' : 'Data gudep diperbarui.',
        ]);

        return to_route($isNew ? 'pembina.members.index' : 'pembina.dashboard');
    }
}
