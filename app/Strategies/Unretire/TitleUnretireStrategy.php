<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;

class TitleUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    public function unretire($model)
    {
        throw_unless($model->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $model->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $model->activate($unretiredDate);
        $model->updateStatusAndSave();
    }
}
