<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\WrestlerData;
use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Create a wrestler.
     */
    public function handle(WrestlerData $wrestlerData): Wrestler
    {
        /** @var Wrestler $wrestler */
        $wrestler = $this->wrestlerRepository->create($wrestlerData);

        if (isset($wrestlerData->start_date)) {
            $this->wrestlerRepository->employ($wrestler, $wrestlerData->start_date);
        }

        return $wrestler;
    }
}
