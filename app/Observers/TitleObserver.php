<?php

namespace App\Observers;

use App\Models\Title;
use App\Enums\TitleStatus;

class TitleObserver
{
    public function saving(Title $title)
    {
        if ($title->isRetired()) {
            $title->status = TitleStatus::RETIRED;
        } elseif ($title->isActive()) {
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
