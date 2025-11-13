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
        Schema::table('equipments', function (Blueprint $table) {
            // mas_sp -> max_sp
            $table->renameColumn('mas_sp', 'max_sp');
            $table->renameColumn('mas_sp_by_lv', 'max_sp_by_lv');

            // skill_amp_by_lv -> skill_amp_by_level
            $table->renameColumn('skill_amp_by_lv', 'skill_amp_by_level');

            // skill_amp_ratio_by_lv -> skill_amp_ratio_by_level
            $table->renameColumn('skill_amp_ratio_by_lv', 'skill_amp_ratio_by_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('max_sp', 'mas_sp');
            $table->renameColumn('max_sp_by_lv', 'mas_sp_by_lv');
            $table->renameColumn('skill_amp_by_level', 'skill_amp_by_lv');
            $table->renameColumn('skill_amp_ratio_by_level', 'skill_amp_ratio_by_lv');
        });
    }
};
