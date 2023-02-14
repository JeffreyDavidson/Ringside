<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StableData
{
    /**
     * Create a new stable data instance.
     *
     * @param  Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  Collection<int, \App\Models\Manager>  $managers
     */
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public Collection $tagTeams,
        public Collection $wrestlers,
        public Collection $managers,
    ) {
    }

    /**
     * Create a DTO from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('start_date'),
            TagTeam::query()->findMany($request->collect('tag_teams')),
            Wrestler::query()->findMany($request->collect('wrestlers')),
            Manager::query()->findMany($request->collect('managers')),
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('start_date'),
            TagTeam::query()->findMany($request->collect('tag_teams')),
            Wrestler::query()->findMany($request->collect('wrestlers')),
            Manager::query()->findMany($request->collect('managers')),
        );
    }
}
