<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BusinessRuleReason;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Throwable;

abstract class BaseBusinessException extends Exception
{
    final public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly BusinessRuleReason $reason = BusinessRuleReason::General,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function reason(): BusinessRuleReason
    {
        return $this->reason;
    }

    protected static function forReason(BusinessRuleReason $reason, string $message): static
    {
        return new static($message, reason: $reason);
    }

    /** @return array{name: mixed, type: string, id: mixed} */
    protected static function extractModelInfo(Model $model): array
    {
        return [
            'name' => $model->getAttribute('name') ?? "ID: {$model->getKey()}",
            'type' => class_basename($model),
            'id' => $model->getKey(),
        ];
    }

    protected static function formatModelContext(Model $model): string
    {
        $info = self::extractModelInfo($model);

        return "{$info['type']} '{$info['name']}'";
    }

    protected static function formatDateContext(?Carbon $date, string $format = 'Y-m-d'): string
    {
        return $date?->format($format) ?? '';
    }
}
