<?php

declare(strict_types=1);

namespace App\ViewModels\Storylines;

use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineStep;

class StorylineViewModel
{
    /** @return array{next: ?StorylineStep, last: ?StorylineDevelopment} */
    public function summary(Storyline $storyline): array
    {
        $storyline = Storyline::query()->withSummaries()->findOrFail($storyline->id);
        $storyline->loadMissing(['nextStep.event.promotion', 'lastDevelopment']);

        return ['next' => $storyline->nextStep, 'last' => $storyline->lastDevelopment];
    }
}
