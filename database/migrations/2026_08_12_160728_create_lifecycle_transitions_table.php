<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lifecycle_transitions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('dimension');
            $table->string('transition');
            $table->timestamp('effective_at');
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'dimension', 'effective_at'], 'lifecycle_transitions_history_index');
        });
    }
};
