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

        if (! is_null($titleData->activation_date) && $this->shouldBeActivated($title)) {
            $this->titleRepository->activate($title, $titleData->activation_date);
        }

        return $title;
    }

    /**
     * Find out if the title can be activated.
     */
    private function shouldBeActivated(Title $title): bool
    {
        if ($title->isCurrentlyActivated()) {
            return false;
        }

        return true;
    }
}
