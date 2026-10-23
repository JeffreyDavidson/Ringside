<?php

declare(strict_types=1);
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Event::class)->unique()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->cascadeOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('slug');
            $table->unsignedInteger('version');
            $table->json('draft');

            foreach (['card', 'results', 'card_source', 'results_source'] as $column) {
                $table->json($column)->nullable();
            }

            foreach (['schedule_fingerprint', 'card_fingerprint', 'results_fingerprint'] as $column) {
                $table->string($column, 64)->nullable();
            }

            foreach (['published_at', 'results_published_at', 'unpublished_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->foreignIdFor(User::class, 'published_by')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'results_published_by')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }
};
