<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tactical_skills', function (Blueprint $table) {
            $table->text('tooltip')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tactical_skills', function (Blueprint $table) {
            $table->string('tooltip')->change();
        });
    }
};
