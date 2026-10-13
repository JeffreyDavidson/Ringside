<?php

declare(strict_types=1);

use App\Models\Events\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_packs', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Event::class)->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('card_fingerprint', 64);
            $table->json('content');
            $table->timestamps();
        });
    }
};
