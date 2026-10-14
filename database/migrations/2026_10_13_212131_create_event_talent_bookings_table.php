<?php

declare(strict_types=1);

use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_talent_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Event::class)->constrained()->restrictOnDelete();
            $table->string('person_type');
            $table->unsignedBigInteger('person_id');
            $table->string('person_name');
            $table->string('person_role');
            $table->string('status')->default(TalentBookingStatus::Proposed->value);
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('fee_minor')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->dateTime('arrival_at')->nullable();
            $table->string('arrival_timezone');
            $table->text('travel_notes')->nullable();
            $table->boolean('terms_need_review')->default(false);
            $table->json('confirmed_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'person_type', 'person_id'], 'talent_booking_person_unique');
        });
    }
};
