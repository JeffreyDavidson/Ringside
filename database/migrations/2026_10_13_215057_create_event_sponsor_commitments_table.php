<?php

declare(strict_types=1);

use App\Enums\Events\SponsorCommitmentStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sponsor_commitments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Event::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(Sponsor::class)->index()->constrained()->restrictOnDelete();
            $table->uuid('submission_key');
            $table->unsignedInteger('version');
            $table->string('sponsor_name', 160);
            $table->string('type', 40);
            $table->string('status', 40)->default(SponsorCommitmentStatus::Pending->value);
            $table->string('description', 500);
            $table->string('responsible', 160)->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->string('timezone', 80);
            $table->text('notes')->nullable();
            $table->string('agreement_reference', 160)->nullable();
            $table->text('evidence_note')->nullable();
            $table->text('evidence_url')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('instruction_label', 160)->nullable();
            $table->text('instruction')->nullable();
            $table->string('instruction_owner', 160)->nullable();
            $table->string('pack_key', 80)->nullable();
            $table->string('pack_instruction_hash', 64)->nullable();
            $table->json('event_context');
            $table->timestamps();
            $table->unique(['event_id', 'submission_key'], 'sponsor_commitment_submission_unique');
            $table->unique(['event_id', 'pack_key'], 'sponsor_commitment_pack_key_unique');
            $table->index(['promotion_id', 'status', 'deadline_at']);
        });
    }
};
