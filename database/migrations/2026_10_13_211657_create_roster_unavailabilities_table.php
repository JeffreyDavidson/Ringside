<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_unavailabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->constrained();
            $table->string('person_type');
            $table->unsignedBigInteger('person_id');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignIdFor(User::class, 'cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['promotion_id', 'person_type', 'person_id', 'cancelled_at', 'starts_on'], 'roster_unavailability_person_dates');
            $table->index(['promotion_id', 'starts_on', 'ends_on']);
            $table->index('created_by');
            $table->index('cancelled_by');
        });
    }
};
