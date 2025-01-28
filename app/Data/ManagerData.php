<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Carbon;

readonly class ManagerData
{
    /**
     * Create a new manager data instance.
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?Carbon $start_date,
    ) {}

    public static function fromForm($data): self
    {
        return new self(
            $data['first_name'],
            $data['last_name'],
            $data['start_date'],
        );
    }
}
