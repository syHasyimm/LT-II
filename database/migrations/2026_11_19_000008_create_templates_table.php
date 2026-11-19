<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // id_card | certificate
            $table->string('applies_to', 20)->default('all'); // peserta | pembina | all
            $table->string('name');
            $table->string('background_path')->nullable();
            $table->decimal('width_mm', 6, 2);
            $table->decimal('height_mm', 6, 2);
            $table->json('fields');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['type', 'applies_to', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
