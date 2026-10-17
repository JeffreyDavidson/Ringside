<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use App\ValueObjects\UsdAmount;

readonly class BudgetLineData
{
    public function __construct(
        public BudgetDirection $direction,
        public BudgetCategory $category,
        public string $description,
        public string $counterparty,
        public ?int $plannedCents,
        public ?int $confirmedCents,
        public ?string $dueDate,
        public ?string $notes,
        public ?string $reason,
        public bool $acknowledge,
    ) {}

    /** @param array{direction: string, category: string, description: string, counterparty: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, acknowledge?: bool} $input */
    public static function fromValidated(array $input): self
    {
        return new self(
            BudgetDirection::from($input['direction']),
            BudgetCategory::from($input['category']),
            trim($input['description']),
            trim($input['counterparty']),
            filled($input['planned'] ?? null) ? UsdAmount::parse($input['planned']) : null,
            filled($input['confirmed'] ?? null) ? UsdAmount::parse($input['confirmed']) : null,
            filled($input['due_date'] ?? null) ? $input['due_date'] : null,
            $input['notes'] ?? null,
            filled($input['reason'] ?? null) ? trim($input['reason']) : null,
            (bool) ($input['acknowledge'] ?? false),
        );
    }
}
