<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Forms;

use App\Data\Storylines\SourceData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

    /**
     * The chosen event, match, segment and standalone date must fit together before anything reads or locks the source.
     * Errors are keyed event, match, segment and target, as the source fields are labelled on the page.
     */
    public function source(?string $fingerprint): SourceData
    {
        $source = new SourceData($this->eventId, $this->matchId, $this->segmentId ?: null, $this->targetOn ?: null, $fingerprint);
        Validator::make(['event' => $source->eventId, 'match' => $source->matchId, 'segment' => $source->segmentId, 'target' => $source->targetOn], [
            'event' => ['nullable', 'integer', 'required_with:match,segment'],
            'match' => ['nullable', 'integer', Rule::prohibitedIf($source->segmentId !== null)],
            'segment' => ['nullable', 'uuid', Rule::requiredIf($source->segmentId !== null)],
            'target' => ['nullable', 'date_format:Y-m-d', Rule::requiredIf($source->targetOn !== null), Rule::prohibitedIf($source->eventId !== null)],
        ])->validate();

        return $source;
    }
}
