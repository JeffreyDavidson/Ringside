<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\TitleData;
use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Update a title.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Data\TitleData  $titleData
     * @return \App\Models\Title
     */
    public function handle(Title $title, TitleData $titleData): Title
    {
        $this->titleRepository->update($title, $titleData);

        if (isset($titleData->activation_date)) {
            if ($title->canBeActivated() || $title->canHaveActivationStartDateChanged($titleData->activation_date)) {
                ActivateAction::run($title, $titleData->activation_date);
            }
        }

        return $title;
    }
}
