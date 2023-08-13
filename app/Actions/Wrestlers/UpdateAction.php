<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\WrestlerData;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
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

        if ($this->shouldBeEmployed($wrestler, $wrestlerData->start_date)) {
            $this->wrestlerRepository->employ($wrestler, $wrestlerData->start_date);
        }

        return $wrestler;
    }

    /**
     * Find out if the wrestler can be employed.
     */
    private function shouldBeEmployed(Wrestler $wrestler, ?Carbon $startDate): bool
    {
        if (is_null($startDate)) {
            return false;
        }

        if ($wrestler->isCurrentlyEmployed()) {
            return false;
        }

        return true;
    }
}
