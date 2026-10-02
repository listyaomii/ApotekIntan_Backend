<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->decimal('harga_beli', 14, 2)->nullable()->after('harga_satuan');
        });
    }

    public function down()
    {
        Schema::table('detail_penjualan', function (Blueprint $table) {
            $table->dropColumn('harga_beli');
        });
    }
};
