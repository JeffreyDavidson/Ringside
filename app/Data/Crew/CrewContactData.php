<?php

declare(strict_types=1);

namespace App\Data\Crew;

/** The details of one crew contact that a save writes. */
readonly class CrewContactData
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $notes,
    ) {}
}
