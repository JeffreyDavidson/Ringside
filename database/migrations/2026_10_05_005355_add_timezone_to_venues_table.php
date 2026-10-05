<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every venue the time zone its calendar day is judged in; existing venues stay on UTC.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->string('timezone')->default('UTC');
        });
    }
};
