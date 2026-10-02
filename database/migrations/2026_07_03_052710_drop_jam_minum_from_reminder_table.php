<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            // Drop kolom lama yang tidak dipakai lagi
            $table->dropColumn('jam_minum');
        });
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->time('jam_minum')->nullable();
        });
    }
};