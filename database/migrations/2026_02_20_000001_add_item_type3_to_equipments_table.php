<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->string('item_type3')->nullable()->after('item_type2');
        });
    }

    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn('item_type3');
        });
    }
};
