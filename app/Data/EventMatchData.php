<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Support\Collection;

class EventMatchData
{
    public function __construct(
        public MatchType $matchType,
        public Collection $referees,
        public ?Collection $titles,
        public Collection $competitors,
        public ?string $preview
    ) {
    }

    /**
     * Retrieve data from the store request.
     *
     * @param  \App\Http\Requests\EventMatches\StoreRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            MatchType::query()->whereKey($request->input('match_type_id'))->sole(),
            Referee::query()->findMany($request->collect('referees')),
            Title::query()->findMany($request->collect('titles')),
            self::getCompetitors($request->collect('competitors')),
            $request->input('preview')
        );
    }

    /**
     * Undocumented function.
     *
     * @param  \Illuminate\Support\Collection $competitors
     * @return \Illuminate\Support\Collection
     */
    public static function getCompetitors(Collection $competitors)
    {
        return $competitors->transform(function ($sideCompetitors) {
            return collect($sideCompetitors)->transform(function ($competitor) {
                $competitor['type'] = $competitor['competitor_type'] === 'wrestler'
                    ? Wrestler::find($competitor['competitor_id'])
                    : TagTeam::find($competitor['competitor_id']);

                return $competitor;
            })->mapToGroups(function ($item) {
                return [str($item['competitor_type'])->plural() => $item['type']];
            });
        });
    }
}
