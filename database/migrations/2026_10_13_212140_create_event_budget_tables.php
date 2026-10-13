<?php

declare(strict_types=1);
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Event::class)->unique()->constrained()->restrictOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->string('timezone');
            $table->integer('version')->default(0);
            $table->timestamps();
        });
        Schema::create('event_budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(EventBudget::class)->index()->constrained()->restrictOnDelete();
            $table->string('direction');
            $table->string('category');
            $table->string('description');
            $table->string('counterparty');
            $table->bigInteger('planned_cents')->nullable();
            $table->bigInteger('confirmed_cents')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('event_budget_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(EventBudgetLine::class)->index()->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->bigInteger('amount_cents');
            $table->date('effective_date');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignIdFor(EventBudgetMovement::class, 'source_id')->nullable()->index()->constrained('event_budget_movements')->restrictOnDelete();
            $table->foreignIdFor(EventBudgetMovement::class, 'replaces_id')->nullable()->unique()->constrained('event_budget_movements')->restrictOnDelete();
            $table->unsignedBigInteger('actor_id');
            $table->string('actor_label');
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('event_budget_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(EventBudget::class)->constrained()->restrictOnDelete();
            $table->integer('version');
            $table->uuid('request_key');
            $table->string('request_hash', 64);
            $table->string('operation');
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('actor_label');
            $table->text('reason')->nullable();
            $table->json('before');
            $table->json('after');
            $table->timestamps();
            $table->unique(['event_budget_id', 'version'], 'budget_revision_version_unique');
            $table->unique(['event_budget_id', 'request_key'], 'budget_revision_request_unique');
        });
    }
};
