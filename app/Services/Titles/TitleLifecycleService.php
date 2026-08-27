<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TitleLifecycleService
{
    public function __construct(
        private readonly TitleLifecycleEligibility $eligibility,
        private readonly TitleActivityPeriodService $activityPeriods,
    ) {}

    public function debut(Title $title, Carbon $date, ?string $notes = null): void
    {
        $this->transition($title, $date, TitleLifecycleTransition::Debut, LifecycleTransitionType::Debuted, $notes);
    }

    public function pull(Title $title, Carbon $date, ?string $notes = null): void
    {
        DB::transaction(function () use ($title, $date, $notes): void {
            $lockedTitle = $this->lock($title);
            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Pull);
            $this->activityPeriods->end($lockedTitle, $date, LifecycleTransitionType::Pulled, $notes);
        });
    }

    public function reinstate(Title $title, Carbon $date, ?string $notes = null): void
    {
        $this->transition($title, $date, TitleLifecycleTransition::Reinstate, LifecycleTransitionType::Reinstated, $notes);
    }

    private function transition(
        Title $title,
        Carbon $date,
        TitleLifecycleTransition $eligibilityTransition,
        LifecycleTransitionType $lifecycleTransition,
        ?string $notes,
    ): void {
        DB::transaction(function () use ($title, $date, $eligibilityTransition, $lifecycleTransition, $notes): void {
            $lockedTitle = $this->lock($title);
            $this->eligibility->ensureAllowed($lockedTitle, $eligibilityTransition);
            $this->activityPeriods->start($lockedTitle, $date, $lifecycleTransition, $notes);
        });
    }

    private function lock(Title $title): Title
    {
        return Title::query()->whereKey($title->getKey())->lockForUpdate()->firstOrFail();
    }
}
