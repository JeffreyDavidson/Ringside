<?php

namespace App\DataTransferObjects;

use App\Http\Requests\EventMatches\UpdateRequest;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Competitor;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;

class EventMatchData
{
    public MatchType $matchType;
    public Collection $referees;
    public ?Collection $titles;
    public Collection $competitors;
    public ?string $preview;

    public static function fromStoreRequest(StoreRequest $request): EventMatchData
    {
        $dto = new self();

        $dto->matchType = MatchType::find($request->input('match_type_id'));
        $dto->referees = Referee::findMany($request->input('referees'));
        $dto->titles = Title::findMany($request->input('titles'));
        $dto->competitors = Competitor::findMany($request->input('competitors'));
        $dto->preview = $request->input('preview');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): EventMatchData
    {
        $dto = new self();

        $dto->matchType = MatchType::find($request->input('match_type_id'));
        $dto->referees = Referee::findMany($request->input('referees'));
        $dto->titles = Title::findMany($request->input('titles'));
        $dto->competitors = Competitor::findMany($request->input('competitors'));
        $dto->preview = $request->input('preview');

        return $dto;
    }
}
