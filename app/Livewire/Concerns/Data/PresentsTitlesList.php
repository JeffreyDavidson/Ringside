<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Titles\Title;
use Livewire\Attributes\Computed;

trait PresentsTitlesList
{
    /** The promotion whose titles the list offers; null offers only titles without a promotion. */
    abstract protected function titlesPromotionId(): ?int;

    /**
     * Title ids already chosen in the form, kept in the list even when they belong to another promotion.
     *
     * @return array<int, mixed>
     */
    abstract protected function selectedTitleIds(): array;

    /**
     * @return array<int|string,string|null>
     */
    #[Computed]
    public function getTitles(): array
    {
        return Title::query()
            ->offeredForPromotion($this->titlesPromotionId(), $this->selectedTitleIds())
            ->pluck('name', 'id')
            ->mapWithKeys(
                static fn (mixed $name, int|string $id): array => [$id => is_string($name) ? $name : null]
            )
            ->all();
    }
}
