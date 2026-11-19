<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penghitung nomor seri per jenis; dikunci saat memberi nomor baru (FR-GEN-03).
        Schema::create('serial_counters', function (Blueprint $table) {
            $table->string('type', 20)->primary();
            $table->unsignedInteger('last')->default(0);
            $table->timestamps();
        });

        Schema::create('generated_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('serial_number', 40)->unique();
            $table->unsignedInteger('sequence');
            $table->string('path')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            // Satu nomor seri per anggota per jenis; generate ulang menimpa file (BR-14).
            $table->unique(['member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_files');
        Schema::dropIfExists('serial_counters');
    }
};
