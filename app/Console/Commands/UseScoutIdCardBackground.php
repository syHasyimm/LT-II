<?php

namespace App\Console\Commands;

use App\Enums\TemplateType;
use App\Models\Template;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('app:latar-idcard-pramuka')]
#[Description('Mengganti gambar latar semua template ID Card dengan desain pramuka (resources/templates/idcard-pramuka.png)')]
class UseScoutIdCardBackground extends Command
{
    public function handle(): int
    {
        $source = resource_path('templates/idcard-pramuka.png');

        if (! is_file($source)) {
            $this->error("File latar tidak ditemukan: {$source}");

            return self::FAILURE;
        }

        $total = Template::query()->count();
        $this->line("Template di database: {$total}");

        if ($total === 0) {
            DefaultTemplates::install();
            $this->info('Tabel template kosong, template bawaan dipasang (ID Card sudah memakai latar pramuka).');

            return self::SUCCESS;
        }

        $templates = Template::query()->where('type', TemplateType::IdCard)->get();

        if ($templates->isEmpty()) {
            $this->warn('Tidak ada template ID Card. Buat template ID Card dulu di halaman admin.');

            return self::SUCCESS;
        }

        $disk = Storage::disk((string) config('camp.private_disk'));
        $png = (string) file_get_contents($source);

        foreach ($templates as $template) {
            $path = 'templates/'.Str::uuid().'.png';
            $disk->put($path, $png);
            $template->forceFill(['background_path' => $path])->save();
            $this->info("✓ {$template->name}");
        }

        $this->line("{$templates->count()} template ID Card diperbarui. Generate ulang ID Card agar latar baru dipakai.");

        return self::SUCCESS;
    }
}
