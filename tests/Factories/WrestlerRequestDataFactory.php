<?php

namespace Tests\Factories;

use App\Models\Wrestler;

class WrestlerRequestDataFactory
{
    private string $name = 'Example Wrestler Name';

    private int $feet = 6;

    private int $inches = 6;

    private int $weight = 240;

    private string $hometown = 'Laraville, FL';

    private ?string $signature_move = null;

    private ?string $started_at = null;

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'feet' => $this->feet,
            'inches' => $this->inches,
            'weight' => $this->weight,
            'hometown' => $this->hometown,
            'signature_move' => $this->signature_move,
            'started_at' => $this->started_at,
        ], $overrides);
    }

    public function withWrestler(Wrestler $wrestler): self
    {
        $clone = clone $this;

        $clone->name = $wrestler->name;
        $clone->height = $wrestler->height;
        $clone->weight = $wrestler->weight;
        $clone->hometown = $wrestler->hometown;
        $clone->signature_move = $wrestler->signature_move;
        $clone->started_at = $wrestler->startedAt?->toDateTimeString();

        return $clone;
    }
}
