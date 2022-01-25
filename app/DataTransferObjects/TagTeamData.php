<?php

namespace App\DataTransferObjects;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TagTeamData
{
    public mixed $name;

    public mixed $signature_move;

    public ?Carbon $start_date;

    public Collection $wrestlers;

    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->date('started_at');
        $dto->wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->date('started_at');
        $dto->wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        return $dto;
    }
}
