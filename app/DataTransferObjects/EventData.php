<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Venue;
use Carbon\Carbon;

class EventData
{
    public mixed $name;

    public ?Carbon $date;

    public ?Venue $venue;

    public mixed $preview;

    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->date = $request->date('date');
        $dto->venue = $request->input('venue_id') ? Venue::whereKey($request->input('venue_id'))->sole() : null;
        $dto->preview = $request->input('preview');

        return $dto;
    }

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
