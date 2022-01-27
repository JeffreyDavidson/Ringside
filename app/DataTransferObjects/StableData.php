<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StableData
{
    /**
     * The name of the stable.
     *
     * @var string
     */
    public string $name;

    /**
     * The start date of the stable's activation.
     *
     * @var Carbon|null
     */
    public ?Carbon $start_date;

    /**
     * The tag teams to be on the stable.
     *
     * @var Collection|null
     */
    public ?Collection $tagTeams;

    /**
     * The wrestlers to be on the stable.
     *
     * @var Collection|null
     */
    public ?Collection $wrestlers;

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Stables\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->start_date = $request->date('started_at');
        $dto->tagTeams = TagTeam::query()->findMany($request->collect('tag_teams'));
        $dto->wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        return $dto;
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Stables\UpdateRequest $request
     *
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->start_date = $request->date('started_at');
        $dto->tagTeams = TagTeam::query()->findMany($request->collect('tag_teams'));
        $dto->wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        return $dto;
    }
}
