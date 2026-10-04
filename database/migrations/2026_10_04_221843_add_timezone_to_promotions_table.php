<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every promotion the time zone its event dates are entered and shown in; existing promotions stay on UTC.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->string('timezone')->default('UTC');
        });
    }
};
