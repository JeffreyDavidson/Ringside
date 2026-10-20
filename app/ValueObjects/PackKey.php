<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Events\SponsorInstructionMode;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

/** The key of an entry in an event pack, stored as `kind:uuid` (for example `segment:0197c3a0-...`). */
final readonly class PackKey implements Stringable
{
    public function __construct(
        public SponsorInstructionMode $kind,
        public string $uuid,
    ) {
        if ($this->kind === SponsorInstructionMode::Remove) {
            throw new InvalidArgumentException('A pack key is for a segment or a setup task.');
        }

        if (! Str::isUuid($this->uuid)) {
            throw new InvalidArgumentException('A pack key must end in a UUID.');
        }
    }

    public static function fromString(string $key): self
    {
        [$kind, $uuid] = array_pad(explode(':', $key, 2), 2, '');

        return new self(SponsorInstructionMode::tryFrom($kind) ?? throw new InvalidArgumentException('Unknown pack key kind.'), $uuid);
    }

    public function __toString(): string
    {
        return "{$this->kind->value}:{$this->uuid}";
    }
}
