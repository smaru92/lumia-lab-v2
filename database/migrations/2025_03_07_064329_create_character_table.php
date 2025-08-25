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
        Schema::create('characters', function (Blueprint $table) {
            $table->integer('id')->primary()->nullable();
            $table->string('name')->nullable();
            $table->integer('max_hp')->nullable();
            $table->integer('max_hp_by_lv')->nullable();
            $table->integer('max_mp')->nullable();
            $table->integer('max_mp_by_lv')->nullable();
            $table->integer('init_extra_point')->nullable();
            $table->integer('max_extra_point')->nullable();
            $table->decimal('attack_power', 10, 3)->nullable();
            $table->decimal('attack_power_by_lv', 10, 3)->nullable();
            $table->decimal('deffence', 10, 3)->nullable();
            $table->decimal('deffence_by_lv', 10, 3)->nullable();
            $table->decimal('hp_regen', 10, 3)->nullable();
            $table->decimal('hp_regen_by_lv', 10, 3)->nullable();
            $table->decimal('sp_regen', 10, 3)->nullable();
            $table->decimal('sp_regen_by_lv', 10, 3)->nullable();
            $table->integer('attack_speed')->nullable();
            $table->integer('attack_speed_limit')->nullable();
            $table->integer('attack_speed_min')->nullable();
            $table->integer('move_speed')->nullable();
            $table->integer('sight_range')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character');
    }
};
