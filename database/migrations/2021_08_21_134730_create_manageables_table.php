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
        Schema::create('manageables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained();
            $table->morphs('manageable');
            $table->dateTime('hired_at');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();
        });
    }
};
