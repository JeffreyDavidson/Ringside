<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TitleStatus;
use App\Models\Title;

class TitleObserver
{
    /**
     * Handle the Title "saved" event.
     */
    public function saving(Title $title): void
    {
        $title->status = match (true) {
            $title->isCurrentlyActivated() => TitleStatus::Active->value,
            $title->hasFutureActivation() => TitleStatus::FutureActivation->value,
            $title->isDeactivated() => TitleStatus::Inactive->value,
            $title->isRetired() => TitleStatus::Retired->value,
            default => TitleStatus::Unactivated->value
        };
    }
}
