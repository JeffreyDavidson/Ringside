<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
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
}
