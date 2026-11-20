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
            [GudepStatus::Submitted, 'SMPN 3 Sleman', 'Jl. Magelang KM 5, Sinduadi, Mlati, Sleman', '20404123', 'Drs. H. Sumarno, M.Pd.'],
            [GudepStatus::Submitted, 'SMAN 1 Bantul', 'Jl. Imogiri Barat KM 5, Bantul', '20404124', 'Siti Aminah, S.Pd.'],
            [GudepStatus::Revision, 'MTsN 2 Kulon Progo', 'Jl. Wates KM 12, Kulon Progo', '20404125', 'Ahmad Fauzi, S.Ag.'],
            [GudepStatus::Verified, 'SMP Muhammadiyah 1 Yogyakarta', 'Jl. Kapas No. 5, Semaki, Umbulharjo, Yogyakarta', '20404126', 'Drs. Bambang Sutrisno'],
            [GudepStatus::Verified, 'SMPN 1 Wonosari', 'Jl. Karangmojo, Wonosari, Gunungkidul', '20404127', 'Sri Lestari, S.Pd.'],
            [GudepStatus::Draft, 'SMPN 4 Depok', 'Jl. Kalasan No. 1, Depok, Sleman', '20404128', 'Rina Wulandari, S.Pd.'],
        ];

        foreach ($plan as [$status, $institution, $address, $npsn, $picName]) {
            $gudep = Gudep::factory()->complete()->create([
                'institution' => $institution,
                'address' => $address,
                'npsn' => $npsn,
                'pic_name' => $picName,
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

            foreach (DocumentType::query()->orderBy('sort')->get() as $type) {
                Document::factory()->for($gudep)->create([
                    'document_type_id' => $type->id,
                    'status' => $documentStatus,
                    'path' => self::PDF,
                    'original_name' => "{$type->code}.pdf",
                ]);
            }

            if ($status === GudepStatus::Revision) {
                $gudep->documents()->where('document_type_id', DocumentType::query()->where('code', 'kesehatan')->value('id'))
                    ->update(['status' => DocumentStatus::Rejected, 'note' => 'Tulisan tidak terbaca, mohon unggah ulang.']);
            }
        }
    }
}
