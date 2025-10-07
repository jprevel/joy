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
        Schema::table('clients', function (Blueprint $table) {
            // Add UUID if not already using UUID
            if (Schema::hasColumn('clients', 'id') && !$this->isUuidColumn('clients', 'id')) {
                // Note: Converting existing int ID to UUID would require data migration
                // For now, we'll add a comment that this should be UUID for new installations
            }

            // Add logo field if not exists
            if (!Schema::hasColumn('clients', 'logo')) {
                $table->string('logo')->nullable()->after('name');
            }

            // Add Trello integration fields
            if (!Schema::hasColumn('clients', 'trello_board_id')) {
                $table->string('trello_board_id')->nullable()->after('logo');
            }

            if (!Schema::hasColumn('clients', 'trello_list_id')) {
                $table->string('trello_list_id')->nullable()->after('trello_board_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['logo', 'trello_board_id', 'trello_list_id']);
        });
    }

    private function isUuidColumn(string $table, string $column): bool
    {
        $columnType = Schema::getColumnType($table, $column);
        return in_array($columnType, ['uuid', 'char']) && Schema::getConnection()
            ->getDoctrineColumn($table, $column)->getLength() === 36;
    }
};
