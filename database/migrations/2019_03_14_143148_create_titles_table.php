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
        Schema::create('titles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('status');
            $table->string('type');
            $table->nullableMorphs('current_champion');
            $table->nullableMorphs('previous_champion');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }
};
