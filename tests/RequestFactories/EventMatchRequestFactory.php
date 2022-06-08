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
            'referees'      => [Referee::factory()->create()->id],
            'titles'        => null,
            'competitors'   => [
                [
                    'competitor_type' => 'wrestler',
                    'competitor_id'   => Wrestler::factory()->create()->id,
                ],
                [
                    'competitor_type' => 'wrestler',
                    'competitor_id'   => Wrestler::factory()->create()->id,
                ],
            ],
            'preview'       => null,
        ];
    }
}
