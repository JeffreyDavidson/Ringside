<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Events\EventPack;

/**
 * The crew assignments and cues saved in an event pack's production section, read by the production sheet, the live
 * show source, the editor and the save. It is a read-side value with no column: it takes the section as the pack types it
 * and answers the questions its callers ask (the active assignments, the cues of one card item) in the saved order,
 * so the stored live-run content comes out identical wherever it is read.
 *
 * @phpstan-import-type Assignment from EventPack
 * @phpstan-import-type Cue from EventPack
 */
final readonly class ProductionPlan
{
    /**
     * @param  list<Assignment>  $assignments
     * @param  list<Cue>  $cues
     */
    public function __construct(private array $assignments, private array $cues, private ?string $notes = null) {}

    /**
     * Read the production section of a pack, or of a live snapshot (which holds only assignments). A missing section
     * is an empty plan.
     *
     * @param  array{notes?: ?string, assignments?: list<Assignment>, cues?: list<Cue>}|null  $production
     */
    public static function fromArray(?array $production): self
    {
        return new self($production['assignments'] ?? [], $production['cues'] ?? [], $production['notes'] ?? null);
    }

    /**
     * Every assignment, removed ones included, in the saved order.
     *
     * @return list<Assignment>
     */
    public function assignments(): array
    {
        return $this->assignments;
    }

    /**
     * The assignments that are still on the plan, in the saved order.
     *
     * @return list<Assignment>
     */
    public function activeAssignments(): array
    {
        return array_values(array_filter($this->assignments, fn (array $assignment): bool => $assignment['active']));
    }

    /**
     * Every cue, retained ones for removed items included, in the saved order.
     *
     * @return list<Cue>
     */
    public function cues(): array
    {
        return $this->cues;
    }

    /**
     * The cues attached to one card item (`match:<id>` or `segment:<uuid>`), in the saved order.
     *
     * @return list<Cue>
     */
    public function cuesFor(string $itemKey): array
    {
        return array_values(array_filter($this->cues, fn (array $cue): bool => $cue['item_key'] === $itemKey));
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
