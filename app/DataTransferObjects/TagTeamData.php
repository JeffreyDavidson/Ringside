<?php

namespace App\DataTransferObjects;

use App\Http\Requests\TagTeams\UpdateRequest;
use App\Http\Requests\TagTeams\StoreRequest;
use \Illumiante\Support\Collection;

class TagTeamData
{
    public string $name;
    public ?string $signature_move;
    public ?string $start_date;
    public Collection $wrestlers;

    public static function fromStoreRequest(StoreRequest $request): TagTeamData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->input('started_at');
        $dto->wrestlers = Wrestler::findMany($request->input('wrestlers'));

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): TagTeamData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->input('started_at');
        $dto->wrestlers = Wrestler::findMany($request->input('wrestlers'));

        return $dto;
    }
}
