<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum SponsorCommitmentType: string
{
    case Announcement = 'announcement';
    case Banner = 'banner';
    case Programme = 'programme';
    case Social = 'social';
    case Other = 'other';

    public function label(): string
    {
        return __('sponsors.'.$this->value);
    }
}
