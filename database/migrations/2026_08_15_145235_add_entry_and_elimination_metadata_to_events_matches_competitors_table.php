<?php

declare(strict_types=1);

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
        Schema::table('events_matches_competitors', function (Blueprint $table) {
            $table->unsignedInteger('entry_order')->nullable()->after('match_side_id');
            $table->unsignedInteger('elimination_order')->nullable()->after('entry_order');
            $table->foreignId('eliminated_by_match_competitor_id')
                ->nullable()
                ->after('elimination_order')
                ->constrained('events_matches_competitors')
                ->nullOnDelete();

            $table->unique(['match_id', 'entry_order']);
            $table->unique(['match_id', 'elimination_order']);
        });
    }
};
