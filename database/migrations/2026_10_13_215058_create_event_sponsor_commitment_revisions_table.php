<?php

declare(strict_types=1);

use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sponsor_commitment_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->foreignIdFor(EventSponsorCommitment::class, 'commitment_id')->index()->constrained('event_sponsor_commitments')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'actor_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->unsignedInteger('version');
            $table->string('operation', 40);
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['commitment_id', 'version'], 'sponsor_commitment_revision_unique');
        });
    }
};
