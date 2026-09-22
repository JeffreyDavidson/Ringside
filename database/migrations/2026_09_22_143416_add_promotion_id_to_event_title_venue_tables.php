<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
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
        foreach (['events', 'venues', 'titles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignIdFor(Promotion::class)
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }
};
