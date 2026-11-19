<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('label');
            $table->string('short_label', 20);
            $table->string('scope', 10); // member | gudep
            $table->json('required_for')->nullable(); // ['peserta', 'pembina_pendamping'] untuk scope member
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // BR-05: jenis dokumen bawaan. Aturan wajib per peran bisa diubah di tabel ini.
        $now = now();
        DB::table('document_types')->insert([
            ['code' => 'kta', 'label' => 'Kartu Tanda Anggota (KTA)', 'short_label' => 'KTA', 'scope' => 'member', 'required_for' => json_encode(['peserta', 'pembina_pendamping']), 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'izin_ortu', 'label' => 'Surat Izin Orang Tua', 'short_label' => 'Izin', 'scope' => 'member', 'required_for' => json_encode(['peserta']), 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'kesehatan', 'label' => 'Surat Keterangan Sehat', 'short_label' => 'Sehat', 'scope' => 'member', 'required_for' => json_encode(['peserta', 'pembina_pendamping']), 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'kesiapan_sekolah', 'label' => 'Surat Kesiapan Sekolah', 'short_label' => 'Kesiapan', 'scope' => 'gudep', 'required_for' => null, 'sort' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
