<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\BusinessRuleReason;
use Exception;
use Illuminate\Database\Eloquent\Model;
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

    protected static function formatModelContext(Model $model): string
    {
        $name = $model->getAttribute('name');

        if (! is_string($name)) {
            $modelKey = $model->getKey();
            $modelIdentifier = is_int($modelKey) || is_string($modelKey)
                ? $modelKey
                : 'unknown';
            $name = "ID: {$modelIdentifier}";
        }

        return class_basename($model)." '{$name}'";
    }
}
