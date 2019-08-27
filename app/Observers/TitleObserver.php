<?php

namespace App\Observers;

use App\Models\Title;
use App\Enums\TitleStatus;

class TitleObserver
{
    /**
     * Handle the Title "saving" event.
     *
     * @param  App\Models\Title  $title
     * @return void
     */
    public function saving(Title $title)
    {
        if ($title->is_scheduled) {
            $title->status = TitleStatus::BOOKABLE;
        } elseif ($title->is_retired) {
            $title->status = TitleStatus::RETIRED;
        } else {
            $title->status = TitleStatus::PENDING_INTRODUCTION;
        }
    }
}
