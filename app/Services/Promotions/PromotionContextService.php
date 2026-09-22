<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Model;

class PromotionContextService
{
    private ?Promotion $promotion = null;

    private bool $enforced = false;

    public function set(Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }

    public function clear(): void
    {
        $this->promotion = null;
        $this->enforced = false;
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

    public function enforce(): void
    {
        $this->enforced = true;
    }

    public function isEnforced(): bool
    {
        return $this->enforced;
    }

    public function owns(Model $model): bool
    {
        if (! $this->enforced || ! $this->promotion instanceof Promotion) {
            return false;
        }

        $promotionKey = $this->promotion->getKey();

        if ($model instanceof EventMatch) {
            return $model->event->promotion_id === $promotionKey;
        }

        $modelPromotionId = $model->getAttribute('promotion_id');

        return is_int($promotionKey) && $modelPromotionId === $promotionKey;
    }
}
