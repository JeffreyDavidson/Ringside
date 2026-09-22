<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Models\Promotions\Promotion;

class PromotionContext
{
    private ?Promotion $promotion = null;

    public function set(Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }

    public function clear(): void
    {
        $this->promotion = null;
    }

    public function current(): ?Promotion
    {
        return $this->promotion;
    }

    public function required(): Promotion
    {
        if (! $this->promotion instanceof Promotion) {
            throw new \LogicException('No active promotion context has been established.');
        }

        return $this->promotion;
    }
}
