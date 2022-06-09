<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TitleStatus;
use App\Models\Title;

class TitleObserver
{
    /**
     * Handle the Title "saved" event.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function saving(Title $title)
    {
        $title->status = match (true) {
            $title->isCurrentlyActivated() => TitleStatus::ACTIVE,
            $title->hasFutureActivation() => TitleStatus::FUTURE_ACTIVATION,
            $title->isDeactivated() => TitleStatus::INACTIVE,
            $title->isRetired() => TitleStatus::RETIRED,
            default => TitleStatus::UNACTIVATED
        };
    }
}
