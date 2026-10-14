<?php

declare(strict_types=1);

use App\Models\Events\EventTalentBooking;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_talent_booking_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(EventTalentBooking::class, 'booking_id')->constrained('event_talent_bookings')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('operation');
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->foreignIdFor(User::class, 'actor_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->timestamps();
            $table->unique(['booking_id', 'version'], 'talent_booking_revision_unique');
        });
    }
};
