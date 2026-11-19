<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GudepStatus;
use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTemplateRequest;
use App\Models\ActivityLog;
use App\Models\GeneratedFile;
use App\Models\Member;
use App\Models\Template;
use App\Services\Files\PrivateFileService;
use App\Services\Pdf\RenderSubject;
use App\Services\Pdf\TemplateRenderer;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Kelola template ID Card dan Sertifikat (FR-TPL-01..05, desain D3). */
class TemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/templates/index', [
            'templates' => Template::query()->orderBy('type')->orderByDesc('is_active')->orderBy('name')->get()
                ->map(fn (Template $template) => $this->summary($template)),
            'targets' => collect(TemplateTarget::cases())->map(fn ($target) => ['value' => $target->value, 'label' => $target->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(TemplateType::class)],
            'applies_to' => ['required', Rule::enum(TemplateTarget::class)],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $type = TemplateType::from($data['type']);
        $base = collect(DefaultTemplates::definitions())->first(fn ($definition) => $definition['type'] === $type);

        $template = Template::query()->create([
            'type' => $type,
            'applies_to' => $data['applies_to'],
            'name' => $data['name'],
            'width_mm' => $base['width_mm'],
            'height_mm' => $base['height_mm'],
            'fields' => $base['fields'],
            'is_active' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template dibuat. Unggah gambar latar lalu atur posisi field.']);

        return to_route('admin.templates.edit', $template);
    }

    public function edit(Template $template): Response
    {
        $type = $template->type;

        $members = Member::query()
            ->whereHas('gudep', fn ($query) => $query->whereIn('status', [GudepStatus::Verified, GudepStatus::Submitted]))
            ->with('gudep')
            ->orderBy('gudep_id')->orderBy('id')
            ->limit(40)
            ->get();

        $samples = collect([['id' => null, 'label' => 'Aisyah Putri Ramadhani (contoh)', 'values' => RenderSubject::sample($this->sampleSerial($type))->values, 'photo_url' => null]])
            ->merge($members->map(fn (Member $member) => [
                'id' => $member->id,
                'label' => "{$member->name} · {$member->gudep->name}",
                'values' => RenderSubject::fromMember($member, $this->serialFor($member, $type))->values,
                'photo_url' => $member->photo_path ? route('members.photo', $member->id) : null,
            ]));

        return Inertia::render('admin/templates/edit', [
            'template' => [
                ...$this->summary($template),
                'fields' => $template->fields,
            ],
            'samples' => $samples->values(),
            'targets' => collect(TemplateTarget::cases())->map(fn ($target) => ['value' => $target->value, 'label' => $target->label()]),
        ]);
    }

    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $data = $request->validated();

        $template->fill([
            'name' => $data['name'],
            'applies_to' => $data['applies_to'],
            'fields' => array_values($data['fields']),
        ])->save();

        ActivityLog::record('template.updated', $template, ['name' => $template->name], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template disimpan.']);

        return back(303);
    }

    public function uploadBackground(Request $request, Template $template, PrivateFileService $files): RedirectResponse
    {
        $request->validate([
            'background' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:8192'],
        ], [], ['background' => 'gambar latar']);

        $old = $template->background_path;
        /** @var UploadedFile $file */
        $file = $request->file('background');
        $template->forceFill(['background_path' => $files->store($file, 'templates')])->save();
        $files->delete($old);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gambar latar diperbarui.']);

        return back(303);
    }

    public function background(Template $template, PrivateFileService $files): StreamedResponse
    {
        abort_unless($template->background_path && $files->exists($template->background_path), 404);

        return $files->inline($template->background_path, 'latar-template.'.pathinfo($template->background_path, PATHINFO_EXTENSION));
    }

    public function activate(Template $template): RedirectResponse
    {
        $template->activate();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$template->name} kini aktif untuk {$template->applies_to->label()}."]);

        return back(303);
    }

    public function duplicate(Template $template, PrivateFileService $files): RedirectResponse
    {
        $copy = $template->replicate(['is_active']);
        $copy->name = "{$template->name} (salinan)";
        $copy->is_active = false;

        if ($template->background_path && $files->exists($template->background_path)) {
            $copy->background_path = 'templates/'.Str::uuid().'.'.pathinfo($template->background_path, PATHINFO_EXTENSION);
            $files->put($copy->background_path, $files->get($template->background_path));
        }

        $copy->save();

        return to_route('admin.templates.edit', $copy);
    }

    public function destroy(Template $template, PrivateFileService $files): RedirectResponse
    {
        if ($template->is_active) {
            return back(303)->withErrors(['template' => 'Template aktif tidak dapat dihapus. Aktifkan template lain terlebih dahulu.']);
        }

        $files->delete($template->background_path);
        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template dihapus.']);

        return to_route('admin.templates.index');
    }

    /** Pratinjau PDF dengan data contoh atau anggota terpilih (FR-TPL-04). */
    public function preview(Request $request, Template $template, TemplateRenderer $renderer): HttpResponse
    {
        $member = $request->filled('member') ? Member::query()->with('gudep')->find($request->integer('member')) : null;
        $subject = $member
            ? RenderSubject::fromMember($member, $this->serialFor($member, $template->type))
            : RenderSubject::sample($this->sampleSerial($template->type));

        return $renderer->pdf($template, [$subject])->stream('pratinjau-'.str($template->name)->slug().'.pdf');
    }

    /** @return array<string, mixed> */
    private function summary(Template $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'type' => $template->type->value,
            'type_label' => $template->type->label(),
            'applies_to' => $template->applies_to->value,
            'applies_label' => $template->applies_to->label(),
            'width_mm' => $template->width_mm,
            'height_mm' => $template->height_mm,
            'is_active' => $template->is_active,
            'background_url' => $template->background_path ? route('admin.templates.background', ['template' => $template->id, 'v' => substr(md5($template->background_path), 0, 8)]) : null,
            'updated_at' => $template->updated_at?->translatedFormat('j M Y, H.i'),
        ];
    }

    private function sampleSerial(TemplateType $type): string
    {
        return sprintf('%s-%d-%s-0021', config('camp.serial_prefix'), config('camp.event_year'), $type === TemplateType::IdCard ? 'ID' : 'CERT');
    }

    private function serialFor(Member $member, TemplateType $type): string
    {
        return GeneratedFile::query()->where('member_id', $member->id)->where('type', $type)->value('serial_number')
            ?? $this->sampleSerial($type);
    }
}
