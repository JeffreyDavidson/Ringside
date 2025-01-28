<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Wrestler;
use Illuminate\Support\Carbon;

readonly class TagTeamData
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
    ) {}

    public static function fromForm($data): self
    {
        return new self(
            $data['name'],
            $data['signature_move'],
            $data['start_date'],
            $data['wrestlerA'],
            $data['wrestlerB'],
        );
    }
}
