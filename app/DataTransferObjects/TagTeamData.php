<?php

namespace App\DataTransferObjects;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TagTeamData
{
    /**
     * The name of the tag team.
     *
     * @var string
     */
    public string $name;

    /**
     * The signature move of the tag team.
     *
     * @var string|null
     */
    public ?string $signature_move;

    /**
     * The start date of the wrestler's employment.
     *
     * @var Carbon|null
     */
    public ?Carbon $start_date;

    /**
     * The wrestlers to be on the tag team.
     *
     * @var Collection|null
     */
    public ?Collection $wrestlers;

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\TagTeams\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->date('started_at');
        $dto->wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        return $dto;
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\TagTeams\UpdateRequest $request
     *
     * @return self
     */
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
