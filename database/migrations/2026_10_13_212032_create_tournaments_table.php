<?php

declare(strict_types=1);
use App\Enums\Tournaments\TournamentStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->uuid('request_id')->nullable();
            $table->unique(['promotion_id', 'request_id']);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('entrant_kind');
            $table->unsignedTinyInteger('capacity');
            $table->boolean('repeat_appearances')->default(false);
            $table->string('status')->default(TournamentStatus::Draft->value);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('tournament_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Tournament::class)->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('seed');
            $table->morphs('competitor');
            $table->boolean('withdrawn')->default(false);
            $table->timestamps();
            $table->unique(['tournament_id', 'seed']);
            $table->unique(['tournament_id', 'competitor_type', 'competitor_id'], 'tournament_entry_identity_unique');
        });
        Schema::create('tournament_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Tournament::class)->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('round');
            $table->unsignedTinyInteger('position');
            $table->unsignedTinyInteger('first_seed')->nullable();
            $table->unsignedTinyInteger('second_seed')->nullable();
            $table->foreignIdFor(Event::class, 'planned_event_id')->nullable()->index()->constrained('events')->restrictOnDelete();
            $table->foreignIdFor(TournamentEntry::class, 'walkover_entry_id')->nullable()->index()->constrained('tournament_entries')->restrictOnDelete();
            $table->text('walkover_reason')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'round', 'position']);
        });
        Schema::create('tournament_match_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Tournament::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(TournamentNode::class, 'node_id')->constrained('tournament_nodes')->restrictOnDelete();
            $table->foreignIdFor(TournamentNode::class, 'active_node_id')->nullable()->unique()->constrained('tournament_nodes')->restrictOnDelete();
            $table->foreignIdFor(EventMatch::class, 'match_id')->unique()->constrained('events_matches')->restrictOnDelete();
            $table->unsignedInteger('attempt');
            $table->timestamps();
            $table->unique(['node_id', 'attempt']);
        });
    }
};
