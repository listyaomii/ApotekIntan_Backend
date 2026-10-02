<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->string('nama_sales', 100)->nullable()->after('nama_supplier');
        });
    }

    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn('nama_sales');
        });
    }
};