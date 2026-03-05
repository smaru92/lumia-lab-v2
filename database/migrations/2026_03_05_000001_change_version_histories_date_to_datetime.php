<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE version_histories MODIFY start_date DATETIME NOT NULL');
        DB::statement('ALTER TABLE version_histories MODIFY end_date DATETIME NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE version_histories MODIFY start_date DATE NOT NULL');
        DB::statement('ALTER TABLE version_histories MODIFY end_date DATE NOT NULL');
    }
};
