<?php

declare(strict_types=1);

use App\ValueObjects\ProductionPlan;

/** @return array{id: string, contact_id: int, name: string, role: string, station: ?string, call_at: ?string, timezone: string, offset: ?string, setup: ?string, during: ?string, strike: ?string, active: bool} */
function planAssignment(string $id, bool $active): array
{
    return ['id' => $id, 'contact_id' => 1, 'name' => 'Alex', 'role' => 'sound', 'station' => null, 'call_at' => null,
        'timezone' => 'UTC', 'offset' => null, 'setup' => null, 'during' => null, 'strike' => null, 'active' => $active];
}

/** @return array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role: ?string, owner_id: ?string, owner_name: ?string, owner_station: ?string, owner_role: ?string, owner_contact_id: ?int, pronunciation: ?string, track: ?string, url: ?string} */
function planCue(string $id, string $itemKey): array
{
    return ['id' => $id, 'item_key' => $itemKey, 'subject' => 'Subject', 'trigger' => 'Trigger', 'instruction' => 'Do it', 'role' => null, 'owner_id' => null, 'owner_name' => null,
        'owner_station' => null, 'owner_role' => null, 'owner_contact_id' => null, 'pronunciation' => null, 'track' => null, 'url' => null];
}

describe('reading a saved production', function (): void {
    it('lists only the active assignments, in the saved order', function (): void {
        // Arrange
        $plan = ProductionPlan::fromArray(['notes' => null, 'cues' => [], 'assignments' => [
            planAssignment('a', true),
            planAssignment('b', false),
            planAssignment('c', true),
        ]]);

        // Act
        $active = $plan->activeAssignments();

        // Assert
        expect(array_column($active, 'id'))->toBe(['a', 'c']);
    });

    it('lists the cues of one item in the saved order', function (): void {
        // Arrange
        $plan = ProductionPlan::fromArray(['notes' => null, 'assignments' => [], 'cues' => [
            planCue('one', 'match:1'),
            planCue('two', 'segment:x'),
            planCue('three', 'match:1'),
        ]]);

        // Act
        $cues = $plan->cuesFor('match:1');

        // Assert
        expect(array_column($cues, 'id'))->toBe(['one', 'three'])
            ->and($plan->cuesFor('match:9'))->toBeEmpty();
    });

    it('returns every assignment, every cue and the notes as saved', function (): void {
        // Arrange
        $assignments = [planAssignment('a', false)];
        $cues = [planCue('one', 'match:1')];

        // Act
        $plan = ProductionPlan::fromArray(['notes' => 'Private', 'assignments' => $assignments, 'cues' => $cues]);

        // Assert
        expect($plan->assignments())->toBe($assignments)
            ->and($plan->cues())->toBe($cues)
            ->and($plan->notes())->toBe('Private');
    });

    it('reads a missing production, or a live snapshot with only assignments, as empty', function (?array $production): void {
        // Arrange
        $plan = ProductionPlan::fromArray($production);

        // Act
        $read = [$plan->activeAssignments(), $plan->cues(), $plan->cuesFor('match:1'), $plan->notes()];

        // Assert
        expect($read)->toBe([[], [], [], null]);
    })->with([
        'no production' => [null],
        'an empty production' => [[]],
    ]);

    it('reads a live snapshot that holds only assignments', function (): void {
        // Arrange
        $plan = ProductionPlan::fromArray(['assignments' => [planAssignment('a', true)]]);

        // Act
        $cues = $plan->cues();

        // Assert
        expect($cues)->toBeEmpty()
            ->and(array_column($plan->activeAssignments(), 'id'))->toBe(['a']);
    });
});
