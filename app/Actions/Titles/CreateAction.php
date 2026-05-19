<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

    /**
     * Create a title.
     *
     * This handles the complete title creation workflow:
     * - Creates the title record with name, description, and championship type
     * - Creates active status record if debut_date is provided
     * - Establishes the title as available for championship competition
     * - Sets up the foundation for future championship lineage
     *
     * @param  TitleData  $titleData  The data transfer object containing title information
     * @return Title The newly created title instance
     *
     * @example
     * ```php
     * $titleData = new TitleData([
     *     'name' => 'World Heavyweight Championship',
     *     'debut_date' => now()
     * ]);
     * $title = CreateAction::run($titleData);
     * ```
     */
    public function handle(TitleData $titleData): Title
    {
        return DB::transaction(function () use ($titleData): Title {
            // Create the base title record
            $title = Title::query()->create([
                'name' => $titleData->name,
                'type' => $titleData->type,
            ]);

            // Create active status if debut_date is provided
            if (isset($titleData->debut_date)) {
                // Create activity period for title debut
                $title->activityPeriods()->updateOrCreate(
                    ['ended_at' => null],
                    ['started_at' => $titleData->debut_date->toDateTimeString()]
                );
            }

            return $title;
        });
    }
}
