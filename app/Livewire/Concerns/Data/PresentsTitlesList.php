<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Titles\Title;
use Livewire\Attributes\Computed;

trait PresentsTitlesList
{
    /**
     * @return array<int|string,string>
     */
    #[Computed]
    public function getTitles(): array
    {
        return Title::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Title $title): array => [$title->id => $title->name ?? ''])
            ->all();
    }
}
