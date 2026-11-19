<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudep_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->string('status', 20)->default('pending')->index();
            $table->text('note')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // FR-DOC-03: dokumen yang diganti tidak dihapus, hanya diarsipkan.
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['gudep_id', 'member_id', 'document_type_id', 'archived_at'], 'documents_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
