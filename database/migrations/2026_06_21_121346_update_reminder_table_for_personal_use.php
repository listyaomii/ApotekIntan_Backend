<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->string('nama_obat', 150)->nullable()->after('id_obat');
        });

        // Ubah id_obat dan id_pegawai jadi nullable
        DB::statement('ALTER TABLE reminder MODIFY id_obat INT NULL');
        DB::statement('ALTER TABLE reminder MODIFY id_pegawai INT NULL');
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->dropColumn('nama_obat');
        });
    }
};