<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // This migration was created to fix missing file issue
        // No changes needed in up() method as indexes are handled by other migrations
    }

    public function down()
    {
        // Use raw SQL to safely drop indexes only if they exist
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_optimal');
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_optimal');
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_equipment_optimal');
    }

    private function dropIndexIfExists($tableName, $indexName)
    {
        $database = env('DB_DATABASE');

        // Check if index exists
        $indexExists = \DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$database, $tableName, $indexName]);

        if ($indexExists && $indexExists->count > 0) {
            \DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
        }
    }
};