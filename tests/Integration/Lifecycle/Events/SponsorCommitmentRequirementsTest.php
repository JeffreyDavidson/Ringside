<?php

declare(strict_types=1);

use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Lifecycle\Events\SponsorCommitmentRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Sponsors\Sponsor;
use Illuminate\Support\Carbon;

const SPONSOR_REQUIREMENTS_KEY = '0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6a';

describe('sponsor commitment requirements', function (): void {
    it('allows an event that belongs to a promotion', function (): void {
        // Arrange
        $event = Event::factory()->make(['promotion_id' => 4]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureEventAvailable($event);

        // Assert
        expect($ensure)->not->toThrow(SponsorCommitmentException::class);
    });

    it('refuses an event with no promotion', function (): void {
        // Arrange
        $event = Event::factory()->make(['promotion_id' => null]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureEventAvailable($event);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.event_unavailable'));
    });

    it('allows a new promise with a fresh key and a saved promise at its version', function (?int $savedVersion, int $version): void {
        // Arrange
        $commitment = $savedVersion === null ? null : EventSponsorCommitment::factory()->make(['version' => $savedVersion]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCurrentSubmission($commitment, $version, SPONSOR_REQUIREMENTS_KEY, fn (): bool => false);

        // Assert
        expect($ensure)->not->toThrow(RecordChangedException::class);
    })->with([
        'a new promise' => [null, 0],
        'a saved promise' => [3, 3],
    ]);

    it('refuses a submission that is stale, repeated or has no real key', function (?int $savedVersion, int $version, string $key, bool $keyUsed): void {
        // Arrange
        $commitment = $savedVersion === null ? null : EventSponsorCommitment::factory()->make(['version' => $savedVersion]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCurrentSubmission($commitment, $version, $key, fn (): bool => $keyUsed);

        // Assert
        expect($ensure)->toThrow(RecordChangedException::class, __('core.errors.record.changed'));
    })->with([
        'a stale version' => [3, 2, SPONSOR_REQUIREMENTS_KEY, false],
        'a new promise at a later version' => [null, 1, SPONSOR_REQUIREMENTS_KEY, false],
        'a repeated key' => [null, 0, SPONSOR_REQUIREMENTS_KEY, true],
        'a key that is not a uuid' => [null, 0, 'invalid', false],
        'a stale version and a key that is not a uuid' => [3, 2, 'invalid', false],
    ]);

    it('does not look up a key that is not a UUID or a stale version', function (?int $savedVersion, int $version, string $key): void {
        // Arrange
        $commitment = $savedVersion === null ? null : EventSponsorCommitment::factory()->make(['version' => $savedVersion]);
        $lookups = 0;

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCurrentSubmission($commitment, $version, $key, function () use (&$lookups): bool {
            $lookups++;

            return false;
        });

        // Assert
        expect($ensure)->toThrow(RecordChangedException::class)
            ->and($lookups)->toBe(0);
    })->with([
        'a new promise with a key that is not a uuid' => [null, 0, 'invalid'],
        'a stale version' => [3, 2, SPONSOR_REQUIREMENTS_KEY],
        'a new promise at a later version' => [null, 1, SPONSOR_REQUIREMENTS_KEY],
    ]);

    it('lets a saved promise repeat its key', function (): void {
        // Arrange
        $commitment = EventSponsorCommitment::factory()->make(['version' => 3]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCurrentSubmission($commitment, 3, SPONSOR_REQUIREMENTS_KEY, fn (): bool => true);

        // Assert
        expect($ensure)->not->toThrow(RecordChangedException::class);
    });

    it('allows a promise on a live event and sponsor, or one that keeps its archived sponsor', function (bool $eventArchived, bool $sponsorArchived, ?int $savedSponsorId): void {
        // Arrange
        $event = Event::factory()->make(['deleted_at' => $eventArchived ? Carbon::parse('2026-01-01') : null]);
        $sponsor = Sponsor::factory()->make(['id' => 8, 'archived_at' => $sponsorArchived ? Carbon::parse('2026-01-01') : null]);
        $commitment = $savedSponsorId === null ? null : EventSponsorCommitment::factory()->make(['sponsor_id' => $savedSponsorId]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCanSelect($event, $sponsor, $commitment);

        // Assert
        expect($ensure)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'a new promise on a live event and sponsor' => [false, false, null],
        'a saved promise on an archived event' => [true, false, 8],
        'a saved promise keeping its archived sponsor' => [false, true, 8],
    ]);

    it('refuses a new promise on an archived event or a promise moved to an archived sponsor', function (bool $eventArchived, bool $sponsorArchived, ?int $savedSponsorId): void {
        // Arrange
        $event = Event::factory()->make(['deleted_at' => $eventArchived ? Carbon::parse('2026-01-01') : null]);
        $sponsor = Sponsor::factory()->make(['id' => 8, 'archived_at' => $sponsorArchived ? Carbon::parse('2026-01-01') : null]);
        $commitment = $savedSponsorId === null ? null : EventSponsorCommitment::factory()->make(['sponsor_id' => $savedSponsorId]);

        // Act
        $ensure = fn () => (new SponsorCommitmentRequirements)->ensureCanSelect($event, $sponsor, $commitment);

        // Assert
        expect($ensure)->toThrow(SponsorCommitmentException::class, __('sponsors.archived'));
    })->with([
        'a new promise on an archived event' => [true, false, null],
        'a new promise for an archived sponsor' => [false, true, null],
        'a saved promise moved to an archived sponsor' => [false, true, 9],
    ]);
});
