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
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->string('meta_tier')->nullable()->after('weapon_type');
            $table->decimal('meta_score', 10, 3)->nullable()->after('weapon_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
