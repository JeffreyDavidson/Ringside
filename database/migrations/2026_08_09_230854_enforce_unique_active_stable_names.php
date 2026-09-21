<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateNames = DB::table('stables')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($duplicateNames->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce unique active stable names. Resolve duplicate active names first: '
                .$duplicateNames->join(', ')
            );
        }

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('The active stable name index is currently supported only on SQLite.');
        }

        $connection->statement(
            'CREATE UNIQUE INDEX stables_active_name_unique ON stables (name) WHERE deleted_at IS NULL'
        );
    }
};
