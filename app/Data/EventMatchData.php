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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

readonly class EventMatchData
{
    /**
     * Create a new event match data instance.
     *
     * @param  EloquentCollection<int, Referee>  $referees
     * @param  EloquentCollection<int, Title>  $titles
     * @param  Collection<"wrestlers"|"tag_teams", array<int, Wrestler|TagTeam>>  $competitors
     */
    public function __construct(
        public MatchType $matchType,
        public EloquentCollection $referees,
        public EloquentCollection $titles,
        public Collection $competitors,
        public ?string $preview
    ) {
    }

    /**
     * Retrieve data from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        /** @var EloquentCollection<int, Referee> $referees */
        $referees = Referee::query()->findMany($request->collect('referees'));

        /** @var EloquentCollection<int, Title> $titles */
        $titles = Title::query()->findMany($request->collect('titles'));

        return new self(
            MatchType::query()->whereKey($request->integer('match_type_id'))->sole(),
            $referees,
            $titles,
            self::getCompetitors($request->collect('competitors')),
            $request->string('preview')->value()
        );
    }

    /**
     * Undocumented function.
     *
     * @param  Collection<"wrestlers"|"tag_teams", array<int, Wrestler|TagTeam>>  $competitors
     * @return Collection<"wrestlers"|"tag_teams", array<int, Wrestler|TagTeam>>
     */
    private static function getCompetitors(Collection $competitors): Collection
    {
        return $competitors->transform(function (array $sideCompetitors) {
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
