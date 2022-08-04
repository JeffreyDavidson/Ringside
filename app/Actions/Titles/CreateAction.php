<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\TitleData;
use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Create a title.
     *
     * @param  \App\Data\TitleData  $titleData
     * @return \App\Models\Title
     */
    public function handle(TitleData $titleData): Title
    {
        /** @var \App\Models\Title $title */
        $title = $this->titleRepository->create($titleData);

        if (isset($titleData->activation_date)) {
            ActivateAction::run($title, $titleData->activation_date);
        }

        return $title;
    }
}
