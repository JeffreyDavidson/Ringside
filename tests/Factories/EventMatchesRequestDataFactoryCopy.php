<?php

namespace Tests\Factories;

use App\Models\MatchType;
use App\Models\Referee;

class EventMatchesRequestDataFactoryCopy
{
    private const DEFAULT_MATCH_TYPE = 1;
    private const DEFAULT_REFEREE_ID = 1;
    private const DEFAULT_COMPETITORS = [1, 2];

    private array $matches = [];

    public function __construct(
        private int $match_type_id,
        private int $referee_id,
        private ?int $title_id,
        private array $competitors,
        private ?string $preview
    ) {
        $this->match_type_id = MatchType::first()->id ?? self::DEFAULT_MATCH_TYPE;
        $this->referee_id = Referee::factory()->create()->id ?? self::DEFAULT_REFEREE_ID;
        $this->title_id = $title_id;
        $this->competitors = $competitors ?: self::DEFAULT_COMPETITORS;
        $this->preview = $preview;
        $this->matches = [
            [
                'match_type_id' => $this->match_type_id,
                'referee_id'    => $this->referee_id,
                'title_id'      => $this->title_id,
                'competitors'   => $this->competitors,
                'preview'       => $this->preview,
            ],
        ];
    }

    public function create(array $params = []): static
    {
        return new static(
            $params['match_type_id'],
            $params['referee_id'],
            $params['title_id'] ?? null,
            $params['competitors'] ?? [],
            $params['preview'] ?? null,
        );
    }

    public function overrideMatches(array $matches)
    {
        return array_replace_recursive([
            [
                'match_type_id' => $this->match_type_id,
                'referee_id'    => $this->referee_id,
                'title_id'      => $this->title_id,
                'competitors'   => $this->competitors,
                'preview'       => $this->preview,
            ],
        ], $matches);
    }
}
