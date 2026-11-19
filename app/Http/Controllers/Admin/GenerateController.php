<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GudepStatus;
use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateFileJob;
use App\Models\ActivityLog;
use App\Models\GeneratedFile;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\Template;
use App\Models\User;
use App\Services\Files\PrivateFileService;
use App\Services\Pdf\RenderSubject;
use App\Services\Pdf\TemplateRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

/** Generate dan unduh ID Card / Sertifikat (FR-GEN-01..04, FR-GEN-06). */
class GenerateController extends Controller
{
    public function index(Request $request, string $jenis): Response
    {
        $type = self::type($jenis);
        $gudeps = Gudep::query()
            ->where('status', GudepStatus::Verified)
            ->withCount('members')
            ->orderBy('name')
            ->get();
        $generatedPerGudep = GeneratedFile::query()
            ->where('type', $type)
            ->whereNotNull('path')
            ->join('members', 'members.id', '=', 'generated_files.member_id')
            ->selectRaw('members.gudep_id, count(*) as total, max(generated_files.generated_at) as last_at')
            ->groupBy('members.gudep_id')
            ->get()
            ->keyBy('gudep_id');

        return Inertia::render('admin/generate', [
            'type' => $type->value,
            'slug' => $jenis,
            'label' => $type->label(),
            'templates' => collect(TemplateTarget::cases())->map(fn (TemplateTarget $target) => [
                'target' => $target->label(),
                'name' => Template::query()->where('type', $type)->where('applies_to', $target)->where('is_active', true)->value('name'),
            ]),
            'stats' => [
                'gudeps' => $gudeps->count(),
                'members' => (int) $gudeps->sum('members_count'),
                'generated' => GeneratedFile::query()->where('type', $type)->whereNotNull('path')->count(),
            ],
            'gudeps' => $gudeps->map(fn (Gudep $gudep) => [
                'id' => $gudep->id,
                'name' => $gudep->name,
                'institution' => $gudep->institution,
                'members' => $gudep->members_count,
                'generated' => (int) ($generatedPerGudep->get($gudep->id)?->getAttribute('total') ?? 0),
                'last_at' => $generatedPerGudep->has($gudep->id)
                    ? CarbonImmutable::parse((string) $generatedPerGudep->get($gudep->id)?->getAttribute('last_at'))->setTimezone((string) config('camp.timezone'))->translatedFormat('j M, H.i')
                    : null,
            ]),
            'members' => Member::query()
                ->whereHas('gudep', fn ($query) => $query->where('status', GudepStatus::Verified))
                ->with('gudep:id,name')
                ->orderBy('gudep_id')->orderBy('name')
                ->get()
                ->map(fn (Member $member) => ['value' => (string) $member->id, 'label' => "{$member->name} · {$member->gudep->name}"]),
            'recent' => GeneratedFile::query()
                ->where('type', $type)
                ->whereNotNull('path')
                ->with('member.gudep')
                ->latest('generated_at')
                ->limit(10)
                ->get()
                ->map(fn (GeneratedFile $file) => [
                    'id' => $file->id,
                    'serial' => $file->serial_number,
                    'name' => $file->member->name,
                    'gudep' => $file->member->gudep->name,
                    'generated_at' => $file->generated_at?->setTimezone((string) config('camp.timezone'))->translatedFormat('j M Y, H.i'),
                ]),
            'batch' => $this->batch($request),
        ]);
    }

    public function store(Request $request, string $jenis): RedirectResponse
    {
        $type = self::type($jenis);
        $data = $request->validate([
            'scope' => ['required', Rule::in(['member', 'gudep', 'all'])],
            'member_id' => ['required_if:scope,member', 'nullable', 'integer'],
            'gudep_id' => ['required_if:scope,gudep', 'nullable', 'integer'],
        ], [
            'member_id.required_if' => 'Pilih anggota penerima.',
            'gudep_id.required_if' => 'Pilih gudep penerima.',
        ]);

        // FR-GEN-01: hanya anggota gudep terverifikasi yang bisa dipilih.
        $members = Member::query()
            ->whereHas('gudep', fn ($query) => $query->where('status', GudepStatus::Verified))
            ->when($data['scope'] === 'member', fn ($query) => $query->whereKey($data['member_id']))
            ->when($data['scope'] === 'gudep', fn ($query) => $query->where('gudep_id', $data['gudep_id']))
            ->pluck('id');

        if ($members->isEmpty()) {
            return back(303)->withErrors(['scope' => 'Tidak ada anggota gudep terverifikasi untuk dipilih.']);
        }

        foreach (Member::query()->whereIn('id', $members)->distinct()->pluck('role') as $role) {
            if (Template::activeFor($type, $role) === null) {
                return back(303)->withErrors(['scope' => "Belum ada template {$type->label()} aktif untuk {$role->label()}."]);
            }
        }

        /** @var User $admin */
        $admin = $request->user();
        $batch = Bus::batch($members->map(fn ($id) => new GenerateFileJob($id, $type, $admin->id))->all())
            ->name("{$type->label()} · {$members->count()} anggota")
            ->allowFailures()
            ->dispatch();

        ActivityLog::record('generate.started', $admin, ['type' => $type->value, 'scope' => $data['scope'], 'count' => $members->count(), 'batch' => $batch->id], $admin);

        return redirect()->route(self::route($type), ['batch' => $batch->id]);
    }

    /** FR-GEN-04: unduh satu PDF yang sudah dibuat. */
    public function download(GeneratedFile $generatedFile, PrivateFileService $files): StreamedResponse
    {
        abort_unless($generatedFile->path && $files->exists($generatedFile->path), 404);

        return $files->inline($generatedFile->path, $this->fileName($generatedFile));
    }

    /**
     * PDF cetak satu gudep: satu kartu per halaman atau lembar A4 3 × 3
     * (FR-GEN-04, FR-GEN-06). Memakai nomor seri yang sudah diberikan.
     */
    public function print(Request $request, string $jenis, Gudep $gudep, TemplateRenderer $renderer): HttpResponse
    {
        $type = self::type($jenis);
        $mode = $request->query('mode') === 'sheet' && $type === TemplateType::IdCard ? 'sheet' : 'single';

        $files = GeneratedFile::query()
            ->where('type', $type)
            ->whereHas('member', fn ($query) => $query->where('gudep_id', $gudep->id))
            ->with('member.gudep')
            ->orderBy('sequence')
            ->get();

        abort_if($files->isEmpty(), 404, 'Belum ada berkas yang dibuat untuk gudep ini.');

        // Template bisa berbeda untuk peserta dan pembina: kelompokkan per template.
        /** @var array<int, array{template: Template, subjects: list<RenderSubject>}> $groups */
        $groups = [];

        foreach ($files as $file) {
            $template = Template::activeFor($type, $file->member->role);

            if ($template !== null) {
                $groups[$template->id]['template'] = $template;
                $groups[$template->id]['subjects'][] = RenderSubject::fromMember($file->member, $file->serial_number);
            }
        }

        abort_if($groups === [], 404, 'Belum ada template aktif.');

        $first = reset($groups);

        // dompdf merender satu dokumen: gabungkan halaman dari tiap template.
        $pdf = count($groups) === 1
            ? $renderer->pdf($first['template'], $first['subjects'], $mode)
            : $renderer->pdfFromHtml(
                self::mergeHtml(array_values(array_map(fn (array $group) => $renderer->html($group['template'], $group['subjects'], $mode), $groups))),
                $first['template'],
                $mode,
            );

        return $pdf->stream(Str::slug("{$type->label()} {$gudep->name}").($mode === 'sheet' ? '-a4' : '').'.pdf');
    }

    /** FR-GEN-04: ZIP massal per gudep atau seluruhnya, dari PDF yang tersimpan. */
    public function zip(Request $request, string $jenis, PrivateFileService $files): StreamedResponse
    {
        $type = self::type($jenis);
        $gudepId = $request->integer('gudep') ?: null;

        $generated = GeneratedFile::query()
            ->where('type', $type)
            ->whereNotNull('path')
            ->when($gudepId, fn ($query) => $query->whereHas('member', fn ($member) => $member->where('gudep_id', $gudepId)))
            ->with('member.gudep')
            ->orderBy('sequence')
            ->get();

        abort_if($generated->isEmpty(), 404, 'Belum ada berkas yang dibuat.');

        $name = Str::slug($type->label().' '.($gudepId ? $generated->first()->member->gudep->name : 'semua')).'.zip';

        return response()->streamDownload(function () use ($generated, $files) {
            $zip = new ZipStream(sendHttpHeaders: false, defaultEnableZeroHeader: true);

            foreach ($generated as $file) {
                if ($file->path && $files->exists($file->path)) {
                    $folder = Str::slug($file->member->gudep->name);
                    $zip->addFile("{$folder}/{$this->fileName($file)}", $files->get($file->path));
                }
            }

            $zip->finish();
        }, $name, ['Content-Type' => 'application/zip']);
    }

    /** @return array<string, mixed>|null */
    private function batch(Request $request): ?array
    {
        $id = $request->query('batch');
        $batch = is_string($id) ? Bus::findBatch($id) : null;

        if (! $batch instanceof Batch) {
            return null;
        }

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
            'failed' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
        ];
    }

    private function fileName(GeneratedFile $file): string
    {
        return $file->serial_number.'-'.Str::slug($file->member->name).'.pdf';
    }

    /** @param  list<string>  $documents */
    private static function mergeHtml(array $documents): string
    {
        $head = (string) Str::before($documents[0], '<body>');
        $last = count($documents) - 1;
        $bodies = array_map(
            // Hanya halaman terakhir dokumen terakhir yang tanpa pemisah halaman.
            fn (string $html, int $index) => $index === $last
                ? Str::between($html, '<body>', '</body>')
                : str_replace('page last', 'page', Str::between($html, '<body>', '</body>')),
            $documents,
            array_keys($documents),
        );

        return $head.'<body>'.implode('', $bodies).'</body></html>';
    }

    public static function type(string $jenis): TemplateType
    {
        return match ($jenis) {
            'id-card' => TemplateType::IdCard,
            'sertifikat' => TemplateType::Certificate,
            default => abort(404),
        };
    }

    public static function route(TemplateType $type): string
    {
        return $type === TemplateType::IdCard ? 'admin.id-cards.index' : 'admin.certificates.index';
    }
}
