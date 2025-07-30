<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Models\TagTeams\TagTeam;
use App\Services\TagTeamLifecycleService;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction
{
    use AsAction;

    /**
     * Create a new employ action instance.
     */
    public function __construct(
        protected TagTeamLifecycleService $lifecycleService
    ) {}

    /**
     * Employ a tag team using the lifecycle service.
     *
     * This action serves as a focused interface for employment while delegating
     * all complex business logic, validation, and lifecycle management to the
     * centralized TagTeamLifecycleService. This ensures consistency across all
     * lifecycle operations and eliminates code duplication.
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When tag team cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Young Bucks')->first();
     * EmployAction::run($tagTeam);
     *
     * // Employ with specific start date
     * EmployAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $tagTeam->ensureCanBeEmployed();

        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($tagTeam, $employmentDate): void {
            $this->lifecycleService->handleEmployment($tagTeam, $employmentDate, true);
        });
    }
}
