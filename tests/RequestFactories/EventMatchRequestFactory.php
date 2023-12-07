<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Wrestler;
use Worksome\RequestFactories\RequestFactory;

class EventMatchRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'match_type_id' => MatchType::first()->id,
            'referees' => [Referee::factory()->bookable()->create()->id],
            'titles' => [],
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
            'preview' => null,
        ];
    }
}
