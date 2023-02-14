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
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->datetime('date')->nullable();
            $table->foreignId('venue_id')->nullable()->constrained();
            $table->text('preview')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
