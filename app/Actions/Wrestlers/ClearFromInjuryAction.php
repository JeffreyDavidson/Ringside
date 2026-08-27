<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualInjuryService;
use Illuminate\Support\Carbon;

class ClearFromInjuryAction
{
    public function __construct(
        private readonly IndividualInjuryService $injury,
    ) {}

    /**
     * Clear a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Ends the injury period through the shared lifecycle component
     * - Potentially restores tag team bookability if all members are now available
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $this->injury->clear($wrestler, $recoveryDate ?? now());
    }
}
