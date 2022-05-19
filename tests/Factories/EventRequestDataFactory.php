<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\Event;

class EventRequestDataFactory
{
    private string $name = 'Example Event Name';

    private ?string $date = null;

    private ?int $venue_id = null;

    private ?string $preview = null;

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'date' => $this->date,
            'venue_id' => $this->venue_id,
            'preview' => $this->preview,
        ], $overrides);
    }

    public function withEvent(Event $event): self
    {
        $clone = clone $this;

        $clone->name = $event->name;

        return $clone;
    }
}
