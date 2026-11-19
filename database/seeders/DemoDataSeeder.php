<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk mencoba panel admin secara lokal:
 * `php artisan db:seed --class=DemoDataSeeder`. Tidak dijalankan otomatis.
 */
class DemoDataSeeder extends Seeder
{
    public const PDF = 'demo/dokumen-contoh.pdf';

    public const PHOTO = 'demo/pas-foto-contoh.png';

    /** File contoh di disk privat agar pratinjau dokumen dan foto bisa dicoba. */
    private function writeSampleFiles(): void
    {
        $disk = Storage::disk((string) config('camp.private_disk'));

        $text = 'DOKUMEN CONTOH - Perkemahan LT 2';
        $stream = "BT /F1 16 Tf 60 420 Td ({$text}) Tj ET";
        $disk->put(self::PDF, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 420 595]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n4 0 obj<</Length ".strlen($stream).">>stream\n{$stream}\nendstream endobj\n5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");

        $image = imagecreatetruecolor(300, 400);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 246, 227, 225));
        imagefilledellipse($image, 150, 150, 140, 160, (int) imagecolorallocate($image, 201, 162, 74));
        imagefilledellipse($image, 150, 400, 260, 260, (int) imagecolorallocate($image, 122, 18, 25));
        ob_start();
        imagepng($image);
        $disk->put(self::PHOTO, (string) ob_get_clean());
    }

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $this->writeSampleFiles();

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

            Member::query()->where('gudep_id', $gudep->id)->update(['photo_path' => self::PHOTO]);

            foreach ($gudep->members as $member) {
                foreach (DocumentType::query()->where('scope', 'member')->get() as $type) {
                    if ($type->requiredFor($member->role)) {
                        Document::factory()->for($gudep)->create([
                            'member_id' => $member->id,
                            'document_type_id' => $type->id,
                            'status' => $documentStatus,
                            'path' => self::PDF,
                            'original_name' => "{$type->code}-".str($member->name)->slug().'.pdf',
                        ]);
                    }
                }
            }

            Document::factory()->for($gudep)->type('kesiapan_sekolah')->create([
                'status' => $documentStatus,
                'path' => self::PDF,
                'original_name' => 'surat-kesiapan-sekolah.pdf',
            ]);

            if ($status === GudepStatus::Revision) {
                $gudep->documents()->where('document_type_id', DocumentType::query()->where('code', 'kesehatan')->value('id'))
                    ->limit(2)->update(['status' => DocumentStatus::Rejected, 'note' => 'Tulisan tidak terbaca, mohon unggah ulang.']);
            }
        }
    }
}
