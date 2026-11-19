<?php

namespace Database\Seeders;

use App\Services\Templates\DefaultTemplates;
use Illuminate\Database\Seeder;

/** Template ID Card dan Sertifikat bawaan (desain FigCraft). */
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        DefaultTemplates::install();
    }
}
