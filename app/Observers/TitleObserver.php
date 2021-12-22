<?php

namespace App\Observers;

use App\Enums\TitleStatus;
use App\Models\Title;

class TitleObserver
{
    /**
     * Handle the Title "saved" event.
     *
     * @param  App\Models\Title $title
     * @return void
     */
    public function saving(Title $title)
    {
        $title->status = match (true) {
            $title->isCurrentlyActivated() => TitleStatus::active(),
            $title->hasFutureActivation() => TitleStatus::future_activation(),
            $title->isDeactivated() => TitleStatus::inactive(),
            $title->isRetired() => TitleStatus::retired(),
            default => TitleStatus::unactivated()
        };
    }
}
