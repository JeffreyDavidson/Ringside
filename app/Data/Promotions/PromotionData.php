<?php

declare(strict_types=1);

namespace App\Data\Promotions;

readonly class PromotionData
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}
}
