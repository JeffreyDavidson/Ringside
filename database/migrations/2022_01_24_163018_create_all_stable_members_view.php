<?php

declare(strict_types=1);

use App\Models\Stable;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createOrReplaceMergeView(
            'all_stable_members',
            [(new Stable)->tagTeams(), (new Stable)->wrestlers()]
        );
    }
};
