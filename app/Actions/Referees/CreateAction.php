<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

    public function __construct(
        private EmployAction $employAction
    ) {}

    /**
     * Create a referee.
     *
     * This handles the complete referee creation workflow:
     * - Creates the referee record with personal and professional details
     * - Uses EmployAction for consistent employment handling if employment_date is provided
     * - Establishes the referee as available for match officiating
     *
     * ARCHITECTURAL PATTERN:
     * Uses EmployAction for employment handling, following the same pattern as other
     * referee actions for consistency.
     *
     * @param  RefereeData  $refereeData  The data transfer object containing referee information
     * @return Referee The newly created referee instance
     *
     * @example
     * ```php
     * $refereeData = new RefereeData([
     *     'name' => 'Earl Hebner',
     *     'hometown' => 'Richmond, VA',
     *     'employment_date' => now()
     * ]);
     * $referee = CreateAction::run($refereeData);
     * ```
     */
    public function handle(RefereeData $refereeData): Referee
    {
        return DB::transaction(function () use ($refereeData): Referee {
            // Create the base referee record
            /** @var Referee $referee */
            $referee = Referee::query()->create([
                'first_name' => $refereeData->first_name,
                'last_name' => $refereeData->last_name,
            ]);

            // Handle employment using EmployAction for consistency
            if (! is_null($refereeData->employment_date)) {
                $employmentDate = DateHelper::resolveDate($refereeData->employment_date);
                $this->employAction->handle($referee, $employmentDate);
            }

            return $referee;
        });
    }
}
