<?php

declare(strict_types=1);

namespace App\Data\Storylines;

readonly class StorylineData
{
    public function __construct(public string $title, public string $premise, public ?string $payoff, public string $submissionId) {}
}
