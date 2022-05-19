<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_match_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_match_id')->constrained();
            $table->morphs('competitor');
            $table->tinyInteger('side_number');
            $table->timestamps();
        });
    }
};
