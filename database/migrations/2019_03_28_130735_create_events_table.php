<?php

use App\Models\Events\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->datetime('date')->nullable();
            $table->foreignIdFor(Venue::class)->nullable();
            $table->text('preview')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
