<?php

declare(strict_types=1);

use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\SponsorInstructionMode;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Lifecycle\Events\SponsorInstructionRequirements;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\ValueObjects\PackKey;
use Illuminate\Database\Eloquent\Collection;

const SPONSOR_FINGERPRINT = 'abc123';
const SPONSOR_UUID = '0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6a';
const SPONSOR_OTHER_UUID = '0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6b';

/**
 * Runs the guards with a card that is in sync, then lets a test change one thing.
 *
 * @param  array{commitment?: array<string, mixed>, pack?: ?EventPack, matches?: array<int>, run?: ?EventLiveRun, preview?: array{hash: string, target: mixed}, version?: int, packVersion?: int, fingerprint?: string, current?: string}  $changes
 */
function ensureSponsorInstruction(SponsorInstructionData $data, array $changes = []): void
{
    $commitment = EventSponsorCommitment::factory()->make([
        'version' => 2, 'instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'pack_key' => null, ...$changes['commitment'] ?? [],
    ]);
    $matches = new Collection(array_map(fn (int $id): EventMatch => (new EventMatch)->forceFill(['id' => $id]), $changes['matches'] ?? [7]));

    resolve(SponsorInstructionRequirements::class)->ensureCanSave(
        $commitment,
        array_key_exists('pack', $changes) ? $changes['pack'] : null,
        $matches,
        $changes['run'] ?? null,
        $changes['preview'] ?? ['hash' => 'hash-1', 'target' => ['id' => SPONSOR_UUID]],
        $changes['current'] ?? SPONSOR_FINGERPRINT,
        $changes['version'] ?? 2,
        $changes['packVersion'] ?? 0,
        $changes['fingerprint'] ?? SPONSOR_FINGERPRINT,
        $data,
    );
}

function sponsorInstruction(SponsorInstructionMode $mode = SponsorInstructionMode::Setup, ?PackKey $target = null, ?int $after = null, ?string $reason = null, string $hash = 'hash-1'): SponsorInstructionData
{
    return new SponsorInstructionData($mode, $target, $after, $hash, $reason);
}

function sponsorPack(string $fingerprint = SPONSOR_FINGERPRINT, int $version = 1): EventPack
{
    return (new EventPack)->forceFill(['version' => $version, 'card_fingerprint' => $fingerprint]);
}

describe('sponsor instruction requirements', function (): void {
    it('allows a reviewed instruction on an unchanged card', function (): void {
        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction());

        // Assert
        expect($ensure)->not->toThrow(Throwable::class);
    });

    it('refuses an instruction reviewed against something that has moved', function (array $changes): void {
        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(), $changes);

        // Assert
        expect($ensure)->toThrow(RecordChangedException::class);
    })->with([
        'commitment version' => [['version' => 1]],
        'pack version' => [['pack' => null, 'packVersion' => 3]],
        'card fingerprint' => [['current' => 'other']],
        'preview hash' => [['preview' => ['hash' => 'hash-2', 'target' => null]]],
    ]);

    it('refuses an instruction while the saved pack no longer matches the card', function (): void {
        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(), ['pack' => sponsorPack('stale'), 'packVersion' => 1]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.pack_review_required'));
    });

    it('refuses a target of the wrong kind', function (): void {
        // Arrange
        $data = sponsorInstruction(SponsorInstructionMode::Setup, new PackKey(SponsorInstructionMode::Segment, SPONSOR_UUID));

        // Act
        $ensure = fn () => ensureSponsorInstruction($data);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.invalid_target'));
    });

    it('refuses a target the pack does not contain', function (): void {
        // Arrange
        $data = sponsorInstruction(SponsorInstructionMode::Setup, new PackKey(SponsorInstructionMode::Setup, SPONSOR_UUID));

        // Act
        $ensure = fn () => ensureSponsorInstruction($data, ['preview' => ['hash' => 'hash-1', 'target' => null]]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.invalid_target'));
    });

    it('refuses to point a linked commitment at another entry', function (array $target): void {
        // Arrange
        $data = sponsorInstruction(SponsorInstructionMode::Setup, $target['key']);
        $linked = 'setup:'.SPONSOR_OTHER_UUID;

        // Act
        $ensure = fn () => ensureSponsorInstruction($data, ['commitment' => ['pack_key' => $linked]]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.invalid_target'));
    })->with([
        'a different entry' => [['key' => new PackKey(SponsorInstructionMode::Setup, SPONSOR_UUID)]],
        'a new entry' => [['key' => null]],
    ]);

    it('refuses to remove with a target', function (): void {
        // Arrange
        $data = sponsorInstruction(SponsorInstructionMode::Remove, new PackKey(SponsorInstructionMode::Setup, SPONSOR_UUID), reason: 'Withdrawn');

        // Act
        $ensure = fn () => ensureSponsorInstruction($data, ['commitment' => ['pack_key' => 'setup:'.SPONSOR_UUID]]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.invalid_target'));
    });

    it('refuses a removal without a reason or without an entry to remove', function (?string $reason, ?string $packKey): void {
        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(SponsorInstructionMode::Remove, reason: $reason), ['commitment' => ['pack_key' => $packKey]]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.reason_required'));
    })->with([
        'no reason' => [null, 'setup:'.SPONSOR_UUID],
        'blank reason' => ['   ', 'setup:'.SPONSOR_UUID],
        'nothing linked' => ['Withdrawn', null],
    ]);

    it('refuses to adopt wording that is incomplete', function (string $field): void {
        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(), ['commitment' => [$field => ' ']]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.instruction_required'));
    })->with(['instruction_label', 'instruction']);

    it('refuses a segment placed after a match that is not on the card', function (?int $after, array $matches): void {
        // Arrange
        $data = sponsorInstruction(SponsorInstructionMode::Segment, after: $after);

        // Act
        $ensure = fn () => ensureSponsorInstruction($data, ['matches' => $matches]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.invalid_target'));
    })->with([
        'unknown match' => [99, [7]],
        'empty card' => [7, []],
    ]);

    it('refuses to change an entry the live show has started', function (string $state): void {
        // Arrange
        $key = new PackKey(SponsorInstructionMode::Setup, SPONSOR_UUID);
        $run = (new EventLiveRun)->forceFill(['content' => ['items' => [['key' => (string) $key, 'state' => $state]]]]);

        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(SponsorInstructionMode::Setup, $key), ['run' => $run, 'commitment' => ['pack_key' => (string) $key]]);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.started_instruction'));
    })->with([LiveItemStatus::Running->value, LiveItemStatus::Finished->value]);

    it('allows an entry the live show has not started', function (): void {
        // Arrange
        $key = new PackKey(SponsorInstructionMode::Setup, SPONSOR_UUID);
        $run = (new EventLiveRun)->forceFill(['content' => ['items' => [['key' => (string) $key, 'state' => LiveItemStatus::Pending->value]]]]);

        // Act
        $ensure = fn () => ensureSponsorInstruction(sponsorInstruction(SponsorInstructionMode::Setup, $key), ['run' => $run, 'commitment' => ['pack_key' => (string) $key]]);

        // Assert
        expect($ensure)->not->toThrow(Throwable::class);
    });
});
