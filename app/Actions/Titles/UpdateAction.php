<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseTitleAction
{
    use AsAction;

    public function __construct(
        TitleRepository $titleRepository
    ) {
        parent::__construct($titleRepository);
    }

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
     *
     * @example
     * ```php
     * // Update title information only
     * $titleData = new TitleData([
     *     'name' => 'Updated Championship Name',
     *     'description' => 'New championship description'
     * ]);
     * $updatedTitle = UpdateAction::run($title, $titleData);
     *
     * // Update and debut a new title
     * $titleData = new TitleData([
     *     'name' => 'Brand New Championship',
     *     'debut_date' => Carbon::parse('2024-01-01')
     * ]);
     * $updatedTitle = UpdateAction::run($newTitle, $titleData);
     * ```
     */
    public function handle(Title $title, TitleData $titleData): Title
    {
        return DB::transaction(function () use ($title, $titleData): Title {
            // Update the title's basic information
            $this->titleRepository->update($title, $titleData);

            // Handle conditional debut creation - only debut titles that have never debuted before
            // Note: This will not reactivate pulled titles - use ReinstateAction for that
            if (! is_null($titleData->debut_date) && ! $title->hasDebuted()) {
                $this->titleRepository->createDebut($title, $titleData->debut_date);
            }

            return $title;
        });
    }
}
