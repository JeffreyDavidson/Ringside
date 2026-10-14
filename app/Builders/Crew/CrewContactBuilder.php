<?php

declare(strict_types=1);

namespace App\Builders\Crew;

use App\Models\Crew\CrewContact;
use Illuminate\Database\Eloquent\Builder;

/** @extends Builder<CrewContact> */
class CrewContactBuilder extends Builder
{
    public function matchingName(string $term): static
    {
        $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower(mb_substr($term, 0, 160)));

        return $this->whereRaw(
            "LOWER(name) LIKE ? ESCAPE '!'",
            ["%{$term}%"],
        );
    }
}
