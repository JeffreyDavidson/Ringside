<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetPaymentMethod;
use App\ValueObjects\UsdAmount;

readonly class BudgetMovementData
{
    public function __construct(public BudgetMovementKind $kind, public int $amount, public string $date, public BudgetPaymentMethod $method, public ?string $reference, public ?string $notes, public ?int $sourceId, public bool $acknowledge) {}

    /** @param array{kind: string, amount: string, effective_date: string, method: string, reference?: ?string, notes?: ?string, source_id?: ?int, acknowledge?: bool} $input */
    public static function fromValidated(array $input): self
    {
        return new self(BudgetMovementKind::from($input['kind']), UsdAmount::parse($input['amount']), $input['effective_date'], BudgetPaymentMethod::from($input['method']), $input['reference'] ?? null, $input['notes'] ?? null, isset($input['source_id']) ? (int) $input['source_id'] : null, (bool) ($input['acknowledge'] ?? false));
    }
}
