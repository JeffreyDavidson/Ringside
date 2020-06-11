<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->morphs('activatable');
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->timestamps();

            $table->index('activatable_type');
            $table->index('activatable_id');
            $table->index(['activatable_id', 'activatable_type']);
            $table->index('started_at');
            $table->index('ended_at');
            $table->index(['started_at', 'ended_at']);
            $table->index(['activatable_id', 'activatable_type', 'started_at', 'ended_at'], 'activations_full_index');
            $table->unique(['activatable_id', 'activatable_type', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activations');
    }
}
