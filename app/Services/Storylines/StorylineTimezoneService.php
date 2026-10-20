<?php

declare(strict_types=1);

namespace App\Services\Storylines;

use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StorylineTimezoneService
{
    /**
     * A new account is dated in the promotion's timezone; a correction keeps the timezone it was recorded in.
     *
     * @throws ModelNotFoundException
     */
    public function forDevelopment(Storyline $storyline, ?int $developmentId): string
    {
        if ($developmentId === null) {
            return Promotion::query()->findOrFail($storyline->promotion_id)->timezone;
        }

        return $storyline->developments()->findOrFail($developmentId)->timezone;
    }
}
