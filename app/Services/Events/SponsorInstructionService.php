<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorInstructionMode;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Events\EventSponsorCommitment;
use App\ValueObjects\PackKey;
use InvalidArgumentException;

/**
 * @phpstan-import-type Content from EventPack
 * @phpstan-import-type Segment from EventPack
 * @phpstan-import-type SponsorTask from EventPack
 */
class SponsorInstructionService
{
    /** @return array{label: string, instruction: string, owner: ?string} */
    public function wording(EventSponsorCommitment $commitment): array
    {
        return ['label' => $commitment->instruction_label ?? '', 'instruction' => $commitment->instruction ?? '', 'owner' => $commitment->instruction_owner];
    }

    public function notes(EventSponsorCommitment $commitment): string
    {
        return ($commitment->instruction ?? '').($commitment->instruction_owner === null ? '' : "\n".__('sponsors.task_owner', ['name' => $commitment->instruction_owner]));
    }

    /**
     * @param  Content|array{}  $content
     * @return Segment|SponsorTask|null
     */
    public function target(array $content, ?string $key): ?array
    {
        if ($key === null) {
            return null;
        }

        try {
            $packKey = PackKey::fromString($key);
        } catch (InvalidArgumentException) {
            return null;
        }

        return collect($content[$packKey->kind === SponsorInstructionMode::Segment ? 'segments' : 'sponsor_tasks'] ?? [])->firstWhere('id', $packKey->uuid);
    }

    /** @param array<string, mixed> $value */
    public function hash(array $value): string
    {
        ksort($value);

        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    /** @return array{wording: array{label: string, instruction: string, owner: ?string}, target: Segment|SponsorTask|null, linked: Segment|SponsorTask|null, hash: string} */
    public function preview(Event $event, EventSponsorCommitment $commitment, SponsorInstructionMode $mode, ?string $targetKey, ?int $afterMatchId): array
    {
        $content = $event->pack()->first()->content ?? [];
        $wording = $this->wording($commitment);
        $target = $this->target($content, $targetKey);
        $linked = $this->target($content, $commitment->pack_key);

        return ['wording' => $wording, 'target' => $target, 'linked' => $linked,
            'hash' => $this->hash(['version' => $commitment->version, 'wording' => $wording, 'mode' => $mode->value, 'targetKey' => $targetKey,
                'afterMatchId' => $afterMatchId, 'target' => $target, 'linked' => $linked])];
    }

    /** @param Content|array{} $content */
    public function needsReview(EventSponsorCommitment $commitment, array $content): bool
    {
        if ($commitment->pack_key === null) {
            return false;
        }

        if ($commitment->status === SponsorCommitmentStatus::Cancelled) {
            return true;
        }

        $target = $this->target($content, $commitment->pack_key);

        if ($target === null || $this->hash($target) !== $commitment->pack_instruction_hash || $target['label'] !== $commitment->instruction_label) {
            return true;
        }

        return PackKey::fromString($commitment->pack_key)->kind === SponsorInstructionMode::Segment
            ? ($target['notes'] ?? null) !== $this->notes($commitment)
            : ($target['instruction'] ?? null) !== $commitment->instruction || ($target['owner'] ?? null) !== $commitment->instruction_owner;
    }
}
