<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TitleRetirementService
{
    public function __construct(
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TitleLifecycleEligibility $eligibility,
    ) {}

    public function retire(Title $title, Carbon $retirementDate): void
    {
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($title, $retirementDate, $operationalDate): void {
            $lockedTitle = $this->lock($title);
            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Retire);

            if ($lockedTitle->activityPeriods()->exists() && $lockedTitle->isCurrentlyActive()) {
                $this->endActivityPeriod->handle($lockedTitle, $operationalDate);
            }

            $this->championshipReigns->endCurrentReign($lockedTitle, $retirementDate);
            $this->retirementPeriods->start($lockedTitle, $retirementDate, LifecycleTransitionType::Retired);
        });
    }

    public function unretire(Title $title, Carbon $unretirementDate): void
    {
        DB::transaction(function () use ($title, $unretirementDate): void {
            $lockedTitle = $title->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Unretire);
            $this->retirementPeriods->end($lockedTitle, $unretirementDate, LifecycleTransitionType::Unretired);
        });
    }

    private function lock(Title $title): Title
    {
        return $title->refreshForUpdate();
    }
}
