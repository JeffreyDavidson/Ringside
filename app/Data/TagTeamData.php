<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

class TagTeamData
{
    /**
     * Create a new tag team data instance.
     */
    public function __construct(
        public string $name,
        public ?string $signature_move,
        public ?Carbon $start_date,
        public ?Wrestler $wrestlerA,
        public ?Wrestler $wrestlerB,
    ) {
    }

    /**
     * Create a DTO from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('signature_move'),
            $request->date('start_date'),
            Wrestler::query()->find($request->input('wrestlerA')),
            Wrestler::query()->find($request->input('wrestlerB')),
        );
    }

    /**
     * Create a DTO from the store request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('signature_move'),
            $request->date('start_date'),
            Wrestler::query()->find($request->input('wrestlerA')),
            Wrestler::query()->find($request->input('wrestlerB')),
        );
    }
}
