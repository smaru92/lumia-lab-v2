<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_weapon_character_tag', function (Blueprint $table) {
            $table->id();
            $table->integer('character_id');
            $table->string('weapon_type');
            $table->unsignedBigInteger('character_tag_id');
            $table->timestamps();

            $table->foreign('character_id', 'cwct_character_fk')
                ->references('id')
                ->on('characters')
                ->onDelete('cascade');

            $table->foreign('character_tag_id', 'cwct_tag_fk')
                ->references('id')
                ->on('character_tags')
                ->onDelete('cascade');

            $table->unique(['character_id', 'weapon_type', 'character_tag_id'], 'cwct_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_weapon_character_tag');
    }
};