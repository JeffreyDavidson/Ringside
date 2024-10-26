<?php

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
        Schema::create('stable_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stable_id')->constrained();
            $table->morphs('member');
            $table->datetime('joined_at');
            $table->datetime('left_at')->nullable();
            $table->timestamps();
        });
    }
};
