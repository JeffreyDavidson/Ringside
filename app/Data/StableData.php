<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

readonly class StableData
{
    /**
     * Create a new stable data instance.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager>  $managers
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
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $tagTeams */
        $tagTeams = TagTeam::query()->findMany($request->collect('tag_teams'));

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $wrestlers */
        $wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $managers */
        $managers = Manager::query()->findMany($request->collect('managers'));

        return new self(
            $request->string('name')->value(),
            $request->date('start_date'),
            $tagTeams,
            $wrestlers,
            $managers,
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $tagTeams */
        $tagTeams = TagTeam::query()->findMany($request->collect('tag_teams'));

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $wrestlers */
        $wrestlers = Wrestler::query()->findMany($request->collect('wrestlers'));

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Manager> $managers */
        $managers = Manager::query()->findMany($request->collect('managers'));

        return new self(
            $request->string('name')->value(),
            $request->date('start_date'),
            $tagTeams,
            $wrestlers,
            $managers,
        );
    }
}
