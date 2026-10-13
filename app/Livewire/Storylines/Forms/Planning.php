<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Forms;

use App\Data\Storylines\SourceData;
use Livewire\Form;

class Planning extends Form
{
    public string $heading = '';

    public string $description = '';

    public ?string $payoff = null;

    public ?int $eventId = null;

    public ?int $matchId = null;

    public ?string $segmentId = null;

    public ?string $targetOn = null;

    public string $occurredOn = '';

    public int $sequence = 1;

    public string $reason = '';

    public string $type = 'wrestler';

    public ?int $participantId = null;

    public string $role = '';

    public string $joinedOn = '';

    public ?string $leftOn = null;

    public string $status = '';

    public bool $reopenStep = false;

    /** @var list<int> */
    public array $participantIds = [];

    public function source(?string $fingerprint): SourceData
    {
        return new SourceData($this->eventId, $this->matchId, $this->segmentId ?: null, $this->targetOn ?: null, $fingerprint);
    }
}
