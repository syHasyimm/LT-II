<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk mencoba panel admin secara lokal:
 * `php artisan db:seed --class=DemoDataSeeder`. Tidak dijalankan otomatis.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $plan = [
            [GudepStatus::Submitted, 'SMPN 3 Sleman', 'Kwarcab Sleman'],
            [GudepStatus::Submitted, 'SMAN 1 Bantul', 'Kwarcab Bantul'],
            [GudepStatus::Revision, 'MTsN 2 Kulon Progo', 'Kwarcab Kulon Progo'],
            [GudepStatus::Verified, 'SMP Muhammadiyah 1 Yogyakarta', 'Kwarcab Kota Yogyakarta'],
            [GudepStatus::Verified, 'SMPN 1 Wonosari', 'Kwarcab Gunungkidul'],
            [GudepStatus::Draft, 'SMPN 4 Depok', 'Kwarcab Sleman'],
        ];

        foreach ($plan as [$status, $institution, $region]) {
            $gudep = Gudep::factory()->complete()->create([
                'institution' => $institution,
                'region' => $region,
                'status' => $status,
                'submitted_at' => $status === GudepStatus::Draft ? null : now()->subDays(random_int(0, 5)),
                'verified_at' => $status === GudepStatus::Verified ? now() : null,
                'admin_note' => $status === GudepStatus::Revision ? 'Surat kesehatan dua peserta putri belum terbaca jelas.' : null,
            ]);

            if ($gudep->submitted_at === null) {
                continue; // gudep draft: belum ada foto dan dokumen
            }

            $documentStatus = match ($status) {
                GudepStatus::Verified => DocumentStatus::Approved,
                default => DocumentStatus::Pending,
            };

            Member::query()->where('gudep_id', $gudep->id)->update(['photo_path' => 'photos/demo.jpg']);

            foreach ($gudep->members as $member) {
                foreach (DocumentType::query()->where('scope', 'member')->get() as $type) {
                    if ($type->requiredFor($member->role)) {
                        Document::factory()->for($gudep)->create([
                            'member_id' => $member->id,
                            'document_type_id' => $type->id,
                            'status' => $documentStatus,
                        ]);
                    }
                }
            }

            Document::factory()->for($gudep)->type('kesiapan_sekolah')->create(['status' => $documentStatus]);

            if ($status === GudepStatus::Revision) {
                $gudep->documents()->where('document_type_id', DocumentType::query()->where('code', 'kesehatan')->value('id'))
                    ->limit(2)->update(['status' => DocumentStatus::Rejected, 'note' => 'Tulisan tidak terbaca, mohon unggah ulang.']);
            }
        }
    }
}
