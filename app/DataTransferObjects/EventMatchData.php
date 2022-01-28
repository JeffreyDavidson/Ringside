<?php

namespace App\DataTransferObjects;

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Support\Collection;

class EventMatchData
{
    /**
     * The match type for the match.
     *
     * @var \App\Models\MatchType
     */
    public MatchType $matchType;

    /**
     * The referees assigned to the match.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public Collection $referees;

    /**
     * The titles being contended for the match.
     *
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    public ?Collection $titles;

    /**
     * The competitors competing in the event match.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public Collection $competitors;

    /**
     * The preview description for the match.
     *
     * @var string|null
     */
    public ?string $preview;

    /**
     * Retrieve data from the store request.
     *
     * @param  \App\Http\Requests\EventMatches\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->matchType = MatchType::query()->whereKey($request->input('match_type_id'))->sole();
        $dto->referees = Referee::query()->findMany($request->collect('referees'));
        $dto->titles = Title::query()->findMany($request->collect('titles'));
        $dto->competitors = self::getCompetitors($request->collect('competitors'));
        $dto->preview = $request->input('preview');

        return $dto;
    }

    /**
     * Undocumented function.
     *
     * @param  \Illuminate\Support\Collection $competitors
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCompetitors(Collection $competitors)
    {
        $formattedCompetitors = collect();

        foreach ($competitors as $sideCompetitors) {
            $wrestlers = collect();
            $tagTeams = collect();

            foreach ($sideCompetitors as $competitor) {
                if ($competitor['competitor_type'] === 'wrestler') {
                    $wrestler = Wrestler::find($competitor['competitor_id']);
                    $formattedCompetitors->push(['wrestlers' => collect($wrestlers->push($wrestler))]);
                } elseif ($competitor['competitor_type'] === 'tag_team') {
                    $tagTeam = TagTeam::find($competitor['competitor_id']);
                    $formattedCompetitors->push(['tag_teams' => collect($tagTeams->push($tagTeam))]);
                }
            }
        }

        return $formattedCompetitors;
    }
}
