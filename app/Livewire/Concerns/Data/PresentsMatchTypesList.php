<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Enums\MatchType;
use Livewire\Attributes\Computed;

trait PresentsMatchTypesList
{
    /**
     * @return array<string,string>
     */
    #[Computed]
    public function getMatchTypes(): array
    {
        return MatchType::options();
    }
}
