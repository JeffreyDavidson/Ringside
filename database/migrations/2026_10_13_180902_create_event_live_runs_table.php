<?php

declare(strict_types=1);

use App\Enums\Events\LiveShowStatus;
use App\Models\Events\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_live_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Event::class)->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedInteger('pack_version');
            $table->string('card_fingerprint', 64);
            $table->string('status')->default(LiveShowStatus::Prepared->value);
            $table->json('content');
            $table->dateTime('prepared_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
