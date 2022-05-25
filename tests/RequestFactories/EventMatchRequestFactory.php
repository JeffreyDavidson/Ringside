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
            'match_type_id' => MatchType::factory(),
            'referees'      => Referee::factory(),
            'titles'        => null,
            'competitors'   => [
                'competitor_type' => 'wrestler',
                'competitor_id'   => Wrestler::factory(),
            ],
            'preview'       => null,
        ];
    }
}
