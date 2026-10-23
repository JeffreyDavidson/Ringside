<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\EventPublication;
use App\Models\Scopes\PromotionContextScope;

class ReferencedPosterKeysQuery
{
    /**
     * Every poster file key an event publication still points at: the draft's poster and the released card's poster
     * (the card keeps its own copy, so it can name a file the draft has since replaced). Reads every promotion's
     * publications, because a file is in use whoever owns it.
     *
     * @return list<string>
     */
    public function get(): array
    {
        $keys = [];

        foreach (EventPublication::query()->withoutGlobalScope(PromotionContextScope::class)->select(['id', 'draft', 'card'])->cursor() as $publication) {
            foreach ([$publication->draft['poster'] ?? null, $publication->card['poster'] ?? null] as $poster) {
                if ($poster !== null) {
                    $keys[] = $poster['key'];
                }
            }
        }

        return $keys;
    }
}
