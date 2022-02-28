<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Venue;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class EventData extends DataTransferObject
{
    /**
     * The name of the event.
     *
     * @var string
     */
    public string $name;

    /**
     * The date of the event.
     *
     * @var \Carbon\Carbon|null
     */
    public ?Carbon $date;

    /**
     * The venue to hold the event.
     *
     * @var \App\Models\Venue|null
     */
    public ?Venue $venue;

    /**
     * The preview description for the event.
     *
     * @var string|null
     */
    public ?string $preview;

    /**
     * Retrieve data from the store request.
     *
     * @param  \App\Http\Requests\Events\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->date = $request->date('date');
        $dto->venue = $request->input('venue_id') ? Venue::whereKey($request->input('venue_id'))->sole() : null;
        $dto->preview = $request->input('preview');

        return $dto;
    }

    /**
     * Retrieve data from the update request.
     *
     * @param  \App\Http\Requests\Events\UpdateRequest $request
     *
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->date = $request->date('date');
        $dto->venue = Venue::whereKey($request->input('venue_id'))->sole();
        $dto->preview = $request->input('preview');

        return $dto;
    }
}
