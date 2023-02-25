<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Venue;
use Illuminate\Support\Carbon;

readonly class EventData
{
    /**
     * Create a new event data instance.
     */
    public function __construct(
        public string $name,
        public ?Carbon $date,
        public ?Venue $venue,
        public ?string $preview
    ) {
    }

    /**
     * Retrieve data from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('date'),
            $request->input('venue_id') ? Venue::whereKey($request->input('venue_id'))->sole() : null,
            $request->input('preview')
        );
    }

    /**
     * Retrieve data from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('date'),
            $request->input('venue_id') ? Venue::whereKey($request->input('venue_id'))->sole() : null,
            $request->input('preview')
        );
    }
}
