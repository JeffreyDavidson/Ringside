<?php

namespace Tests\Factories;

use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Wrestler;

class EventMatchRequestDataFactory
{
    private const DEFAULT_MATCH_TYPE = 1;

    private const DEFAULT_REFEREE_ID = [1];

    private const DEFAULT_COMPETITORS = [
        [['competitor_id' => 1, 'competitor_type' => 'wrestler']],
        [['competitor_id' => 2, 'competitor_type' => 'wrestler']],
    ];

    public function __construct()
    {
        Wrestler::factory()->count(4)->create();
        $this->match_type_id = MatchType::first()->id ?? self::DEFAULT_MATCH_TYPE;
        $this->referees = [Referee::factory()->create()->id] ?? self::DEFAULT_REFEREE_ID;
        $this->titles = null;
        $this->competitors = self::DEFAULT_COMPETITORS;
        $this->preview = null;
    }

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'match_type_id' => $this->match_type_id,
            'referees'      => $this->referees,
            'titles'        => $this->titles,
            'competitors'   => $this->competitors,
            'preview'       => $this->preview,
        ], $overrides);
    }
}
