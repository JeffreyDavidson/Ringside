<?php

declare(strict_types=1);

use App\Models\Events\Event;
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
        Schema::create('events_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class);
            $table->unsignedTinyInteger('match_number');
            $table->unsignedBigInteger('match_type_id');
            $table->text('preview')->nullable();
            $table->timestamps();
        });
    }
};
