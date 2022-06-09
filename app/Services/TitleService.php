<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Titles\ActivateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;

class TitleService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TitleRepository
     */
    protected $titleRepository;

    /**
     * Create a new title service instance.
     *
     * @param  \App\Repositories\TitleRepository  $titleRepository
     */
    public function __construct(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }

    /**
     * Create a title with given data.
     *
     * @param  \App\Data\TitleData  $titleData
     * @return \App\Models\Title
     */
    public function create(TitleData $titleData)
    {
        /** @var \App\Models\Title $title */
        $title = $this->titleRepository->create($titleData);

        if (isset($titleData->activation_date)) {
            ActivateAction::run($title, $titleData->activation_date);
        }

        return $title;
    }

    /**
     * Update a given title with given data.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Data\TitleData  $titleData
     * @return \App\Models\Title
     */
    public function update(Title $title, TitleData $titleData)
    {
        $this->titleRepository->update($title, $titleData);

        if (isset($titleData->activation_date)) {
            if ($title->canBeActivated() || $title->canHaveActivationStartDateChanged($titleData->activation_date)) {
                ActivateAction::run($title, $titleData->activation_date);
            }
        }

        return $title;
    }

    /**
     * Delete a given title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function delete(Title $title)
    {
        $this->titleRepository->delete($title);
    }

    /**
     * Restore a given title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function restore(Title $title)
    {
        $this->titleRepository->restore($title);
    }
}
