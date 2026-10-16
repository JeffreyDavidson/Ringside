<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Lifecycle\Events\EventProductionRequirements;

/**
 * The submitted crew and cue plan of one production save. Build it from the output of
 * EventProductionRequirements::validatedInput(); the save checks that shape again before writing.
 *
 * @phpstan-import-type Input from EventProductionRequirements
 */
readonly class EventProductionData
{
    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $cues
     */
    public function __construct(
        public ?string $notes,
        public array $assignments,
        public array $cues,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromValidated(array $input): self
    {
        /** @var Input $input */
        return new self($input['notes'] ?? null, $input['assignments'], $input['cues']);
    }
}
