<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('DROP INDEX IF EXISTS stables_active_name_unique');
        }

        foreach (['wrestlers', 'managers', 'referees', 'tag_teams', 'stables'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignIdFor(Promotion::class)
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if ($isSqlite) {
            DB::statement('CREATE UNIQUE INDEX stables_active_name_unique ON stables (name) WHERE deleted_at IS NULL');
        }
    }
};
