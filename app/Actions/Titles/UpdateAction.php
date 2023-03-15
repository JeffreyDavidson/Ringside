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
     */
    public function handle(Title $title, TitleData $titleData): Title
    {
        $this->titleRepository->update($title, $titleData);

        if (isset($titleData->activation_date)
            && ($title->canBeActivated() || $title->canHaveActivationStartDateChanged($titleData->activation_date))
        ) {
            $this->titleRepository->activate($title, $titleData->activation_date);
        }

        return $title;
    }
}
