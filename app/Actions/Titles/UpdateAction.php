<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Update a title.
     *
     * This handles the complete title update workflow:
     * - Updates title information (name, description, championship type)
     * - Handles conditional debut if debut_date is provided and title is not active
     * - Maintains championship integrity and lineage throughout the update process
     * - Preserves all historical championship and status records
     *
     * @param  Title  $title  The title to update
     * @param  TitleData  $titleData  The updated title information
     * @return Title The updated title instance
     */
    public function handle(Title $title, TitleData $titleData): Title
    {
        return DB::transaction(function () use ($title, $titleData): Title {
            // Update the title's basic information
            $title->update([
                'name' => $titleData->name,
                'type' => $titleData->type,
            ]);

            // Handle conditional debut creation - only debut titles that have never debuted before
            // Note: This will not reactivate pulled titles - use ReinstateAction for that
            if (! is_null($titleData->debut_date) && ! $title->hasActivityPeriods()) {
                $title->activityPeriods()->create([
                    'started_at' => $titleData->debut_date,
                    'ended_at' => null,
                ]);
            }

            return $title;
        });
    }
}
