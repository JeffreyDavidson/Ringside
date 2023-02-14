<?php

declare(strict_types=1);

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
        Schema::create('title_championships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained();
            $table->foreignId('event_match_id')->constrained();
            $table->morphs('champion');
            $table->datetime('won_at');
            $table->datetime('lost_at')->nullable();
            $table->timestamps();
        });
    }
};
