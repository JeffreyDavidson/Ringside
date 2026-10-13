<?php

declare(strict_types=1);
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storylines', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->text('premise');
            $table->text('payoff')->nullable();
            $table->string('status')->default(StorylineStatus::Active->value);
            $table->string('previous_status')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('submission_id')->unique();
            $table->timestamps();
        });
        Schema::create('storyline_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Storyline::class)->index()->constrained()->restrictOnDelete();
            $table->string('participant_type', 20);
            $table->unsignedBigInteger('participant_id');
            $table->index(['participant_type', 'participant_id']);
            $table->string('label', 160);
            $table->string('role', 160)->nullable();
            $table->date('joined_on');
            $table->date('left_on')->nullable();
            $table->unsignedBigInteger('open_participant_id')->nullable()->storedAs('CASE WHEN left_on IS NULL THEN participant_id ELSE NULL END');
            $table->unique(['storyline_id', 'participant_type', 'open_participant_id'], 'storyline_one_open_participant');
            $table->timestamps();
        });
        Schema::create('storyline_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Storyline::class)->index()->constrained()->restrictOnDelete();
            $table->string('heading', 160);
            $table->text('description');
            $table->string('status')->default(StepStatus::Planned->value);
            $table->unsignedInteger('sequence');
            $table->foreignIdFor(Event::class)->nullable()->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(EventMatch::class, 'match_id')->nullable()->index()->constrained('events_matches')->restrictOnDelete();
            $table->uuid('segment_id')->nullable();
            $table->date('target_on')->nullable();
            $table->dateTime('target_at')->nullable();
            $table->string('timezone', 80);
            $table->json('source_context');
            $table->unsignedInteger('version')->default(1);
            $table->uuid('submission_id')->unique();
            $table->timestamps();
        });
        Schema::create('storyline_developments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Storyline::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(StorylineStep::class)->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('heading', 160);
            $table->text('description');
            $table->date('occurred_on');
            $table->string('timezone', 80);
            $table->unsignedInteger('sequence');
            $table->foreignIdFor(Event::class)->nullable()->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(EventMatch::class, 'match_id')->nullable()->index()->constrained('events_matches')->restrictOnDelete();
            $table->uuid('segment_id')->nullable();
            $table->json('source_context');
            $table->json('cast');
            $table->unsignedInteger('version')->default(1);
            $table->uuid('submission_id')->unique();
            $table->dateTime('retracted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('storyline_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Storyline::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class, 'actor_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('operation', 40);
            $table->string('record_type', 40);
            $table->unsignedBigInteger('record_id');
            $table->text('reason')->nullable();
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
    }
};
