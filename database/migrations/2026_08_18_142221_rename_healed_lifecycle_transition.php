<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lifecycle_transitions')
            ->where('transition', 'healed')
            ->update(['transition' => 'cleared_from_injury']);
    }
};
