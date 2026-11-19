<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudeps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gudep_number', 50);
            $table->string('institution');
            $table->string('region');
            $table->string('status', 20)->default('draft')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('deadline_extended_until')->nullable();
            $table->timestamp('revision_deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudeps');
    }
};
