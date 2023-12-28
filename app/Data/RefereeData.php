<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use Illuminate\Support\Carbon;

readonly class RefereeData
{
    /**
     * Create a new referee data instance.
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?Carbon $start_date,
    ) {
    }

    /**
     * Create a DTO from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->string('first_name')->value(),
            $request->string('last_name')->value(),
            $request->date('start_date')
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->string('first_name')->value(),
            $request->string('last_name')->value(),
            $request->date('start_date')
        );
    }
}
