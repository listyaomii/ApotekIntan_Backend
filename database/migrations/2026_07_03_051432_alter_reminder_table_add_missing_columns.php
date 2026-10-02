<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            if (!Schema::hasColumn('reminder', 'nama_obat')) {
                $table->string('nama_obat', 150)->nullable()->after('id_pegawai');
            }
            if (!Schema::hasColumn('reminder', 'jam_minum_list')) {
                $table->json('jam_minum_list')->nullable()->after('nama_obat');
            }
            if (!Schema::hasColumn('reminder', 'hari_aktif')) {
                $table->json('hari_aktif')->nullable()->after('frekuensi');
            }
            if (!Schema::hasColumn('reminder', 'tgl_mulai')) {
                $table->date('tgl_mulai')->nullable()->after('stok');
            }
            if (!Schema::hasColumn('reminder', 'tgl_selesai')) {
                $table->date('tgl_selesai')->nullable()->after('tgl_mulai');
            }
            if (!Schema::hasColumn('reminder', 'stok')) {
                $table->integer('stok')->nullable()->default(0)->after('hari_aktif');
            }
            if (!Schema::hasColumn('reminder', 'dosis')) {
                $table->string('dosis', 100)->nullable()->after('nama_obat');
            }
            if (!Schema::hasColumn('reminder', 'frekuensi')) {
                $table->string('frekuensi', 50)->nullable()->after('dosis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->dropColumn([
                'nama_obat', 'jam_minum_list', 'hari_aktif',
                'tgl_mulai', 'tgl_selesai', 'stok', 'dosis', 'frekuensi'
            ]);
        });
    }
};