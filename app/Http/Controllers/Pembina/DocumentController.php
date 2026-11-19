<?php

namespace App\Http\Controllers\Pembina;

use App\Actions\Documents\StoreDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pembina\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use App\Services\DocumentChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Dokumen persyaratan dan matriks kelengkapan (desain M4). */
class DocumentController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;

        if ($gudep === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Isi data gudep terlebih dahulu.']);

            return to_route('pembina.gudep.edit');
        }

        return Inertia::render('pembina/documents/index', [
            ...self::matrix($gudep),
            'editable' => $user->can('update', $gudep),
            'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
        ]);
    }

    public function store(StoreDocumentRequest $request, StoreDocument $storeDocument): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Gudep $gudep */
        $gudep = $user->gudep;

        $type = DocumentType::query()->findOrFail($request->integer('document_type_id'));
        $member = $request->filled('member_id')
            ? $gudep->members()->findOrFail($request->integer('member_id'))
            : null;

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $storeDocument->handle($gudep, $member, $type, $file, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $type->label.($member ? " {$member->name}" : '').' berhasil diunggah.',
        ]);

        return back();
    }

    /**
     * Data matriks untuk halaman pembina dan panel verifikasi admin.
     *
     * @return array<string, mixed>
     */
    public static function matrix(Gudep $gudep): array
    {
        $checklist = DocumentChecklist::for($gudep);
        $memberTypes = $checklist->memberTypes();
        $members = $gudep->members()->orderByDesc('role')->orderBy('group')->orderBy('id')->get();

        $document = fn ($doc) => $doc ? DocumentResource::make($doc)->resolve() : null;

        return [
            'counts' => $checklist->counts(),
            'columns' => $memberTypes->map(fn (DocumentType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'label' => $type->label,
                'short' => $type->short_label,
            ])->values(),
            'gudepDocuments' => $checklist->gudepTypes()->map(fn (DocumentType $type) => [
                'type' => ['id' => $type->id, 'label' => $type->label],
                'document' => $document($checklist->documentFor(null, $type)),
            ])->values(),
            'rows' => $members->map(fn (Member $member) => [
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'short_name' => self::shortName($member->name),
                    'role_label' => $member->isPembina() ? 'Pembina' : 'Peserta '.ucfirst((string) $member->group?->value),
                    'is_pembina' => $member->isPembina(),
                    'group' => $member->isPembina() ? 'pembina' : $member->group?->value,
                    'kta_number' => $member->kta_number,
                    'birth_label' => $member->birth_date?->translatedFormat('j M Y'),
                    'is_owner' => $member->is_owner,
                    'has_photo' => filled($member->photo_path),
                    'photo_url' => $member->photo_path ? route('members.photo', $member->id) : null,
                ],
                'cells' => $memberTypes->mapWithKeys(fn (DocumentType $type) => [
                    $type->id => [
                        'required' => $type->requiredFor($member->role),
                        'document' => $document($checklist->documentFor($member, $type)),
                    ],
                ]),
            ])->values(),
        ];
    }

    /** "Aisyah Putri Ramadhani" → "Aisyah P. R." */
    private static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($parts) ?? $name;

        return trim($first.' '.collect($parts)->map(fn ($part) => Str::upper(mb_substr($part, 0, 1)).'.')->implode(' '));
    }
}
