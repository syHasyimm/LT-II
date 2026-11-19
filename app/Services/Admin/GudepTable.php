<?php

namespace App\Services\Admin;

use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Models\Gudep;
use App\Services\DocumentChecklist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Daftar gudep untuk admin (FR-VER-02): filter status, pencarian nama gudep
 * atau sekolah, dan paginasi. Dipakai dashboard (D1) dan halaman Gudep.
 */
class GudepTable
{
    /** @return array{all: int, draft: int, submitted: int, revision: int, verified: int} */
    public static function statusCounts(): array
    {
        $counts = Gudep::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'all' => (int) $counts->sum(),
            'draft' => (int) ($counts['draft'] ?? 0),
            'submitted' => (int) ($counts['submitted'] ?? 0),
            'revision' => (int) ($counts['revision'] ?? 0),
            'verified' => (int) ($counts['verified'] ?? 0),
        ];
    }

    /**
     * @return array{filters: array{status: string|null, q: string|null}, rows: LengthAwarePaginator<int, mixed>}
     */
    public static function fromRequest(Request $request, int $perPage = 15): array
    {
        $status = GudepStatus::tryFrom((string) $request->query('status'))?->value;
        $search = trim((string) $request->query('q')) ?: null;

        $paginator = Gudep::query()
            ->withCount([
                'members as peserta_count' => fn (Builder $query) => $query->where('role', MemberRole::Peserta),
                'members as pembina_count' => fn (Builder $query) => $query->where('role', MemberRole::Pembina),
            ])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('institution', 'like', "%{$search}%")
                ->orWhere('gudep_number', 'like', "%{$search}%")))
            ->orderByRaw("case status when 'submitted' then 0 when 'revision' then 1 when 'draft' then 2 else 3 end")
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Gudep $gudep) {
                $counts = DocumentChecklist::for($gudep)->counts();

                return [
                    'id' => $gudep->id,
                    'name' => $gudep->name,
                    'institution' => $gudep->institution,
                    'region' => $gudep->region,
                    'peserta' => (int) $gudep->getAttribute('peserta_count'),
                    'pembina' => (int) $gudep->getAttribute('pembina_count'),
                    'documents' => ['uploaded' => $counts['uploaded'], 'total' => $counts['total'], 'rejected' => $counts['rejected']],
                    'submitted_at' => $gudep->submitted_at?->translatedFormat('j M, H.i'),
                    'status' => $gudep->status->value,
                ];
            });

        return [
            'filters' => ['status' => $status, 'q' => $search],
            'rows' => $paginator,
        ];
    }
}
