<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final readonly class DeletionPeriodCloser
{
    public function __construct(
        private InjuryPeriodManager $injuryPeriods,
        private RetirementPeriodManager $retirementPeriods,
        private SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * Close every open lifecycle period on the deletion date.
     *
     * Employment closes through OpenPeriodEnder so a scheduled employment (one that starts after the
     * date) is not left open on the deleted record: it ends on its own start date, never before it.
     * Restoring the record would otherwise have to close it later and could end it before it began.
     *
     * @param  Model&Employable<*>&Injurable<*>&Retirable<*>&Suspendable<*>  $subject
     */
    public function close(Model&Employable&Injurable&Retirable&Suspendable $subject, Carbon $date): void
    {
        OpenPeriodEnder::end($subject->employments()->getQuery(), 'started_at', 'ended_at', $date);

        if ($subject->currentRetirement()->exists()) {
            $this->retirementPeriods->end($subject, $date, clampToStart: true);
        }

        if ($subject->currentSuspension()->exists()) {
            $this->suspensionPeriods->end($subject, $date, clampToStart: true);
        }

        if ($subject->currentInjury()->exists()) {
            $this->injuryPeriods->end($subject, $date, clampToStart: true);
        }
    }

    /**
     * Reopen the retirement that the subject's deletion closed, so a restored record is still retired.
     *
     * The closed retirement is the latest-ended one when it ended on the latest `Deleted` audit date, or
     * when it had not started yet and was closed on its own start date after that date. A retirement
     * ended by a real unretirement has an `Unretired` audit on its end date and is never reopened.
     * Only retirement comes back; employment, suspension and injury stay closed.
     *
     * @param  Model&Retirable<*>  $subject
     */
    public function reopenRetirement(Model&Retirable $subject): void
    {
        $deletedAt = $this->transitions($subject, LifecycleTransitionType::Deleted)
            ->latest('effective_at')
            ->latest('id')
            ->value('effective_at');

        $retirement = $subject->retirements()
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at')
            ->orderByDesc('id')
            ->first();

        if (! $deletedAt instanceof Carbon || ! $retirement instanceof Retirement || ! $retirement->ended_at instanceof Carbon) {
            return;
        }

        $endedOnDeletion = $retirement->ended_at->equalTo($deletedAt)
            || ($retirement->ended_at->equalTo($retirement->started_at) && $retirement->started_at->gt($deletedAt));

        if (! $endedOnDeletion) {
            return;
        }

        if ($this->transitions($subject, LifecycleTransitionType::Unretired)->where('effective_at', $retirement->ended_at)->exists()) {
            return;
        }

        $retirement->update(['ended_at' => null]);
    }

    /**
     * @param  Model&Retirable<*>  $subject
     * @return Builder<LifecycleTransition>
     */
    private function transitions(Model&Retirable $subject, LifecycleTransitionType $transition): Builder
    {
        return LifecycleTransition::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('transition', $transition);
    }
}
