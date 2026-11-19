<?php

namespace App\Http\Controllers\Pembina;

use App\Actions\Gudep\DeleteMember;
use App\Actions\Gudep\SaveMember;
use App\Enums\TeamGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pembina\SaveMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Anggota gudep: peserta Pa/Pi dan pembina pendamping (desain M3, M3b, M3c). */
class MemberController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $gudep = $this->gudep($request);

        if ($gudep === null) {
            return $this->needGudep();
        }

        $members = $gudep->members()->orderByDesc('is_owner')->orderBy('id')->get();
        $tab = in_array($request->query('tab'), ['pa', 'pi', 'pembina'], true) ? $request->query('tab') : 'pa';

        return Inertia::render('pembina/members/index', [
            'gudep' => ['name' => $gudep->displayName()],
            'tab' => $tab,
            'max' => (int) config('camp.peserta_per_regu'),
            'editable' => $request->user()?->can('update', $gudep) ?? false,
            'members' => [
                'pa' => MemberResource::collection($members->where('group', TeamGroup::Pa)->values())->resolve(),
                'pi' => MemberResource::collection($members->where('group', TeamGroup::Pi)->values())->resolve(),
                'pembina' => MemberResource::collection($members->filter->isPembina()->values())->resolve(),
            ],
            'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
        ]);
    }

    public function create(Request $request, string $regu): Response|RedirectResponse
    {
        abort_unless(in_array($regu, ['pa', 'pi'], true), 404);

        $gudep = $this->gudep($request);

        if ($gudep === null) {
            return $this->needGudep();
        }

        Gate::authorize('update', $gudep);

        $group = TeamGroup::from($regu);
        $count = $gudep->peserta($group)->count();
        $max = (int) config('camp.peserta_per_regu');

        if ($count >= $max) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => "Regu {$group->label()} sudah penuh ({$max}/{$max})."]);

            return to_route('pembina.members.index', ['tab' => $regu]);
        }

        return Inertia::render('pembina/members/form', [
            'member' => null,
            'regu' => $regu,
            'slot' => ['number' => $count + 1, 'max' => $max],
            'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
        ]);
    }

    public function store(SaveMemberRequest $request, SaveMember $saveMember): RedirectResponse
    {
        /** @var Gudep $gudep */
        $gudep = $this->gudep($request);
        $member = $saveMember->handle($gudep, null, $request->validated(), $request->file('photo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$member->name} ditambahkan."]);

        return to_route('pembina.members.index', ['tab' => $request->string('regu')->toString()]);
    }

    public function edit(Member $member): Response
    {
        Gate::authorize('view', $member);

        return Inertia::render('pembina/members/form', [
            'member' => MemberResource::make($member)->resolve(),
            'regu' => $member->isPembina() ? 'pembina' : $member->group?->value,
            'slot' => null,
            'editable' => request()->user()?->can('update', $member) ?? false,
            'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
        ]);
    }

    public function update(SaveMemberRequest $request, Member $member, SaveMember $saveMember): RedirectResponse
    {
        $saveMember->handle($member->gudep, $member, $request->validated(), $request->file('photo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data anggota diperbarui.']);

        return to_route('pembina.members.index', ['tab' => $member->isPembina() ? 'pembina' : $member->group?->value]);
    }

    public function destroy(Member $member, DeleteMember $deleteMember): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $tab = $member->isPembina() ? 'pembina' : $member->group?->value;
        $deleteMember->handle($member);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$member->name} dihapus."]);

        return to_route('pembina.members.index', ['tab' => $tab]);
    }

    private function gudep(Request $request): ?Gudep
    {
        /** @var User $user */
        $user = $request->user();

        return $user->gudep;
    }

    private function needGudep(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => 'Isi data gudep terlebih dahulu.']);

        return to_route('pembina.gudep.edit');
    }
}
