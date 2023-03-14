<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\WrestlerData;
use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Update a wrestler.
     */
    public function handle(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
    {
        $this->wrestlerRepository->update($wrestler, $wrestlerData);

        if (isset($wrestlerData->start_date)) {
            if ($wrestler->canBeEmployed()
                || $wrestler->canHaveEmploymentStartDateChanged($wrestlerData->start_date)
            ) {
                $this->wrestlerRepository->employ($wrestler, $wrestlerData->start_date);
            }
        }

        return $wrestler;
    }
}
