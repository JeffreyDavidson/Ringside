<?php

declare(strict_types=1);

use App\Models\TagTeam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrestlers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('name');
            $table->integer('height');
            $table->integer('weight');
            $table->string('hometown');
            $table->string('signature_move')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
