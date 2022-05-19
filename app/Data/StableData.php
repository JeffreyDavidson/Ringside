<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StableData
{
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public ?Collection $tagTeams,
        public ?Collection $wrestlers
    ) {
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Stables\StoreRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('started_at'),
            TagTeam::query()->findMany($request->collect('tag_teams')),
            Wrestler::query()->findMany($request->collect('wrestlers'))
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Stables\UpdateRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('started_at'),
            TagTeam::query()->findMany($request->collect('tag_teams')),
            Wrestler::query()->findMany($request->collect('wrestlers'))
        );
    }
}
