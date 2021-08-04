<?php

namespace App\Services;

use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeActivatedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Exceptions\CannotBeDeactivatedException;

class TitleService
{
    /**
     * Activate a model.
     *
     *
     * @param \App\Models\Title $title
     * @param  string|null $startedAt
     * @return void
     */
    public function activate($title, $startedAt = null)
    {
        throw_unless($title->canBeActivated(), new CannotBeActivatedException('Entity cannot be employed. This entity is currently employed.'));

        $title->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
        $title->updateStatusAndSave();
    }

    /**
     * Deactivate a model.
     *
     * @param \App\Models\Title $title
     * @param  string|null $deactivatedAt
     * @return void
     */
    public function deactivate($title, $deactivatedAt = null)
    {
        throw_unless($title->canBeDeactivated(), new CannotBeDeactivatedException('Entity cannot be deactivated. This entity is not currently activated.'));

        $title->currentActivation()->update(['ended_at' => $deactivatedAt ?? now()]);
        $title->updateStatusAndSave();
    }

     /**
     * Retire a title.
     *
     * @param  \App\Models\Title $title
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($title, $retiredAt = null)
    {
        throw_unless($title->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        $title->currentActivation()->update(['ended_at' => $retiredDate]);
        $title->retirements()->create(['started_at' => $retiredDate]);
        $title->updateStatusAndSave();
    }

    /**
     * Unretire a title.
     *
     * @param  \App\Models\Title $title
     * @param  string|null $startedAt
     * @return void
     */
    public function unretire($title, $unretiredAt = null)
    {
        throw_unless($title->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $title->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $title->activate($unretiredDate);
        $title->updateStatusAndSave();
    }
}
