<?php

namespace App\Observers;

use App\Enums\TitleStatus;
use App\Models\Title;

class TitleObserver
{
    public function saving(Title $title)
    {
        if ($title->isRetired()) {
            $title->status = TitleStatus::RETIRED;
        } elseif ($title->isCurrentlyActive()) {
            $title->status = TitleStatus::ACTIVE;
        } elseif ($title->isDeactivated()) {
            $title->status = TitleStatus::INACTIVE;
        } elseif ($title->hasFutureActivation()) {
            $title->status = TitleStatus::FUTURE_ACTIVATION;
        } else {
            $title->status = TitleStatus::UNACTIVATED;
        }
    }
}
