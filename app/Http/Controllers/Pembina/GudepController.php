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
            'gudep' => $gudep?->only(['name', 'gudep_number', 'institution', 'region']),
            'editable' => $gudep === null ? $user->can('create', Gudep::class) : $user->can('update', $gudep),
        ]);
    }

    public function update(SaveGudepRequest $request, SaveGudep $saveGudep): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isNew = $user->gudep === null;

        /** @var array{name: string, gudep_number: string, institution: string, region: string} $data */
        $data = $request->validated();
        $saveGudep->handle($user, $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isNew ? 'Data gudep tersimpan. Lanjutkan dengan mengisi anggota.' : 'Data gudep diperbarui.',
        ]);

        return to_route($isNew ? 'pembina.members.index' : 'pembina.dashboard');
    }
}
