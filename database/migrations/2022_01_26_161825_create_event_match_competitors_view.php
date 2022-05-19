<?php

declare(strict_types=1);

use App\Models\EventMatch;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createOrReplaceMergeView(
            'all_match_competitors',
            [(new EventMatch)->wrestlers(), (new EventMatch)->tagTeams()]
        );
    }
};
