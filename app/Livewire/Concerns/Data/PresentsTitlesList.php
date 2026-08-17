<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Titles\Title;
use Livewire\Attributes\Computed;

trait PresentsTitlesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(seconds: 180, cache: true, key: 'titles-list')]
    public function getTitles(): array
    {
        return Title::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Title $title): array => [$title->id => $title->name])
            ->all();
    }
}
