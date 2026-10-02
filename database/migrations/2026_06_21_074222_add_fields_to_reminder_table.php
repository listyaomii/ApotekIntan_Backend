<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->json('hari_aktif')->nullable()->after('frekuensi');
            $table->boolean('status_aktif')->default(true)->after('hari_aktif');
            $table->integer('stok')->nullable()->after('status_aktif');
            $table->string('dosis', 100)->nullable()->after('stok');
        });

        // Ubah jam_minum dari TIME jadi JSON (menyimpan beberapa jam)
        Schema::table('reminder', function (Blueprint $table) {
            $table->json('jam_minum_list')->nullable()->after('jam_minum');
        });
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->dropColumn(['hari_aktif', 'status_aktif', 'stok', 'dosis', 'jam_minum_list']);
        });
    }
};