<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

readonly class EventMatchData
{
    /**
     * Create a new event match data instance.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referee>  $referees
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Title>  $titles
     * @param  \Illuminate\Support\Collection<int, mixed>  $competitors
     */
    public function __construct(
        public MatchType $matchType,
        public Collection $referees,
        public Collection $titles,
        public Collection $competitors,
        public ?string $preview
    ) {
    }

    /**
     * Retrieve data from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referee> $referees */
        $referees = Referee::query()->findMany($request->collect('referees'));

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Title> $titles */
        $titles = Title::query()->findMany($request->collect('titles'));

        return new self(
            MatchType::query()->whereKey($request->integer('match_type_id'))->sole(),
            $referees,
            $titles,
            self::getCompetitors($request->collect('competitors')),
            $request->input('preview')
        );
    }

    /**
     * Undocumented function.
     */
    private static function getCompetitors(Collection $competitors): Collection
    {
        /** @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter */
        return $competitors->transform(function ($sideCompetitors) {
            if (Arr::exists($sideCompetitors, 'wrestlers')) {
                return data_set(
                    $sideCompetitors,
                    'wrestlers',
                    Wrestler::query()->findMany(Arr::get($sideCompetitors, 'wrestlers'))
                );
            }

            if (Arr::exists($sideCompetitors, 'tag_teams')) {
                return data_set(
                    $sideCompetitors,
                    'tag_teams',
                    TagTeam::query()->findMany(Arr::get($sideCompetitors, 'tag_teams'))
                );
            }

            throw new Exception('Roster member type not found');
        });
    }
}
