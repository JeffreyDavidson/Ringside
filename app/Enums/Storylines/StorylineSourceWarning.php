<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

enum StorylineSourceWarning: string
{
    case DeletedSource = 'deleted_source';
    case UndatedSource = 'undated_source';
    case RemovedSegment = 'removed_segment';
    case UnplacedSegment = 'unplaced_segment';
    case SkippedSource = 'skipped_source';

    public function label(): string
    {
        return __("storylines.{$this->value}");
    }

    /** Whether a plan or account that is not explained as history can still be saved against a source with this warning. */
    public function allowsCurrentSave(): bool
    {
        return match ($this) {
            self::UndatedSource, self::SkippedSource => true,
            self::DeletedSource, self::RemovedSegment, self::UnplacedSegment => false,
        };
    }
}
