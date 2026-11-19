<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudep_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->string('group', 5)->nullable();
            $table->string('name');
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('kta_number', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('accompanies', 10)->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_owner')->default(false);
            $table->timestamps();

            $table->index(['gudep_id', 'role', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
