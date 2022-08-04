<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\WrestlerData;
use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Create a wrestler.
     *
     * @param  \App\Data\WrestlerData  $wrestlerData
     * @return \App\Models\Wrestler
     */
    public function handle(WrestlerData $wrestlerData): Wrestler
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = $this->wrestlerRepository->create($wrestlerData);

        if (isset($wrestlerData->start_date)) {
            EmployAction::run($wrestler, $wrestlerData->start_date);
        }

        return $wrestler;
    }
}
