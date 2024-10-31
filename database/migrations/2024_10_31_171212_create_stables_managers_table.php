<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stables_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stable_id')->constrained();
            $table->foreignId('manager_id')->constrained();
            $table->dateTime('hired_at');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stables_managers');
    }
};
