<?php

declare(strict_types=1);

namespace App\Data\Sponsors;

readonly class SponsorData
{
    public function __construct(public string $name, public ?string $contactName = null, public ?string $email = null,
        public ?string $phone = null, public ?string $notes = null, public bool $archived = false) {}
}
