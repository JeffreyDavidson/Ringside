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
        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $fullName = $table->string('full_name');

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $fullName->storedAs("first_name || ' ' || last_name");
            } else {
                $fullName->virtualAs("CONCAT(first_name,' ',last_name)");
            }

            $table->timestamps();
            $table->softDeletes();
        });
    }
};
