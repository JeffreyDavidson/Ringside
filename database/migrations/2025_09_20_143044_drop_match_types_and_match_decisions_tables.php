<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old tables that are no longer needed
        Schema::dropIfExists('match_types');
        Schema::dropIfExists('match_decisions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the tables if migration is rolled back
        Schema::create('match_types', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->tinyInteger('number_of_sides')->nullable();
            $table->timestamps();
        });

        Schema::create('match_decisions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });
    }
};
