<?php

declare(strict_types=1);

namespace App\Builders\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineStatus;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\Eloquent\Builder;

/** @template TModel of Storyline
 * @extends Builder<TModel>
 */
class StorylineBuilder extends Builder
{
    public function withSummaries(): static
    {
        return $this->addSelect(['next_step_id' => StorylineStep::query()->select('storyline_steps.id')
            ->leftJoin('events', 'events.id', '=', 'storyline_steps.event_id')
            ->whereColumn('storyline_steps.storyline_id', 'storylines.id')
            ->where('storyline_steps.status', StepStatus::Planned)
            ->orderByRaw('CASE WHEN COALESCE(events.date, storyline_steps.target_at) IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('COALESCE(events.date, storyline_steps.target_at)')
            ->orderBy('storyline_steps.sequence')->orderBy('storyline_steps.id')->limit(1)]);
    }

    public function visible(): static
    {
        return $this->where('status', '!=', StorylineStatus::Archived);
    }
}
