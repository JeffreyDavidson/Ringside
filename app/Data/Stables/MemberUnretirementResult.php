<?php

declare(strict_types=1);

namespace App\Data\Stables;

use Illuminate\Support\Collection;

/**
 * Data class to track member unretirement results during stable unretirement.
 *
 * This provides detailed feedback about which members were successfully
 * unretired and which failed, along with the specific reasons for failures.
 * This enables better user feedback and administrative reporting.
 */
final class MemberUnretirementResult
{
    public function __construct(
        public readonly Collection $successfulUnretirements,
        public readonly Collection $failedUnretirements,
        public readonly Collection $skippedMembers
    ) {}

    /**
     * Create a new empty result tracker.
     */
    public static function empty(): self
    {
        return new self(
            collect(),
            collect(),
            collect()
        );
    }

    /**
     * Add a successful member unretirement.
     */
    public function addSuccess(mixed $member, string $memberType): void
    {
        $this->successfulUnretirements->push([
            'member' => $member,
            'type' => $memberType,
            'name' => $member->name,
        ]);
    }

    /**
     * Add a failed member unretirement.
     */
    public function addFailure(mixed $member, string $memberType, string $reason): void
    {
        $this->failedUnretirements->push([
            'member' => $member,
            'type' => $memberType,
            'name' => $member->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Add a skipped member (not retired).
     */
    public function addSkipped(mixed $member, string $memberType, string $reason = 'Not retired'): void
    {
        $this->skippedMembers->push([
            'member' => $member,
            'type' => $memberType,
            'name' => $member->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Get total number of members processed.
     */
    public function getTotalProcessed(): int
    {
        return $this->successfulUnretirements->count() + 
               $this->failedUnretirements->count() + 
               $this->skippedMembers->count();
    }

    /**
     * Check if any members were successfully unretired.
     */
    public function hasSuccesses(): bool
    {
        return $this->successfulUnretirements->isNotEmpty();
    }

    /**
     * Check if any members failed to unretire.
     */
    public function hasFailures(): bool
    {
        return $this->failedUnretirements->isNotEmpty();
    }

    /**
     * Get a summary of the unretirement results.
     */
    public function getSummary(): string
    {
        $total = $this->getTotalProcessed();
        $successful = $this->successfulUnretirements->count();
        $failed = $this->failedUnretirements->count();
        $skipped = $this->skippedMembers->count();

        if ($total === 0) {
            return 'No members processed for unretirement.';
        }

        $summary = "Processed {$total} members: ";
        $parts = [];

        if ($successful > 0) {
            $parts[] = "{$successful} successfully unretired";
        }

        if ($failed > 0) {
            $parts[] = "{$failed} failed to unretire";
        }

        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped";
        }

        return $summary . implode(', ', $parts) . '.';
    }

    /**
     * Get detailed failure reasons.
     */
    public function getFailureReasons(): array
    {
        return $this->failedUnretirements->map(function ($failure) {
            return "{$failure['name']} ({$failure['type']}): {$failure['reason']}";
        })->toArray();
    }
}