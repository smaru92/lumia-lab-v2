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
        Schema::create('equipment_skills', function (Blueprint $table) {
            $table->id()->comment('장비 스킬 ID');
            $table->string('name')->comment('스킬 이름');
            $table->text('description')->nullable()->comment('스킬 설명');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_skills');
    }
};
