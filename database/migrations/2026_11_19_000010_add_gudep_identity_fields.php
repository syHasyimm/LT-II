<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudeps', function (Blueprint $table) {
            $table->string('npsn', 8)->nullable()->after('gudep_number');
            $table->string('pic_name', 150)->nullable()->after('institution');
            $table->string('address', 255)->nullable()->after('pic_name');
        });

        // Alamat menggantikan kwartir/wilayah. Data lama dipertahankan.
        DB::table('gudeps')->update(['address' => DB::raw('region')]);

        Schema::table('gudeps', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }

    public function down(): void
    {
        Schema::table('gudeps', function (Blueprint $table) {
            $table->string('region', 150)->nullable()->after('institution');
        });

        DB::table('gudeps')->update(['region' => DB::raw('address')]);

        Schema::table('gudeps', function (Blueprint $table) {
            $table->dropColumn(['npsn', 'pic_name', 'address']);
        });
    }
};
