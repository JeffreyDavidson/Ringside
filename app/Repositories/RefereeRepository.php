<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeEmployment;
use App\Models\Referees\RefereeInjury;
use App\Models\Referees\RefereeRetirement;
use App\Models\Referees\RefereeSuspension;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\RefereeRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Tests\Unit\Repositories\RefereeRepositoryTest;

/**
 * Repository for Referee model business operations and data persistence.
 *
 * Handles all referee related database operations including CRUD operations,
 * employment/retirement/suspension/injury management functionality.
 *
 * @see RefereeRepositoryTest
 */
class RefereeRepository extends BaseRepository implements ManagesEmploymentContract, ManagesInjuryContract, ManagesRetirementContract, ManagesSuspensionContract, RefereeRepositoryInterface
{
    /** @use ManagesEmployment<RefereeEmployment, Referee> */
    use ManagesEmployment;

    /** @use ManagesInjury<RefereeInjury, Referee> */
    use ManagesInjury;

    /** @use ManagesRetirement<RefereeRetirement, Referee> */
    use ManagesRetirement;

    /** @use ManagesSuspension<RefereeSuspension, Referee> */
    use ManagesSuspension;

    /**
     * Create a new referee.
     */
    public function create(RefereeData $refereeData): Referee
    {
        /** @var Referee $referee */
        $referee = Referee::query()->create([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);

        return $referee;
    }

    /**
     * Update a referee.
     */
    public function update(Referee $referee, RefereeData $refereeData): Referee
    {
        $referee->update([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);

        return $referee;
    }

    /**
     * Restore a soft-deleted referee.
     */
    public function restore(Referee $referee): void
    {
        $referee->restore();
    }
}
