<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        ['listings', ['entrepreneur_profile_id']],
        ['messages', ['sender_id']],
        ['reports', ['reporter_id']],
        ['reports', ['listing_id']],
        ['reports', ['reported_user_id']],
        ['reports', ['reviewed_by']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$tableName, $columns]) {
            $indexName = $this->indexName($tableName, $columns);

            if (! Schema::hasIndex($tableName, $columns)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$tableName, $columns]) {
            $indexName = $this->indexName($tableName, $columns);

            if (Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }

    private function indexName(string $tableName, array $columns): string
    {
        return $tableName.'_'.implode('_', $columns).'_index';
    }
};
