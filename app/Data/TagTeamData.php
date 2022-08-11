<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class TagTeamData
{
    /**
     * Create a new tag team data instance.
     *
     * @param  string  $name
     * @param  string|null  $signature_move
     * @param  \Illuminate\Support\Carbon|null  $start_date
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $wrestlers
     */
    public function __construct(
        public string $name,
        public ?string $signature_move,
        public ?Carbon $start_date,
        public Collection $wrestlers
    ) {
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\TagTeams\StoreRequest  $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('signature_move'),
            $request->date('started_at'),
            Wrestler::query()->findMany($request->collect('wrestlers'))
        );
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\TagTeams\UpdateRequest  $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('signature_move'),
            $request->date('started_at'),
            Wrestler::query()->findMany($request->collect('wrestlers'))
        );
    }
}
