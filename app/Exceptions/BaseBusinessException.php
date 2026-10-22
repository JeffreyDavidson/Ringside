<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Shared\BusinessRuleReason;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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

    /** @var array<string, string> */
    private array $translationReplacements = [];

    /**
     * Placeholder values for the translated message of this failure's reason.
     *
     * @return array<string, string>
     */
    public function translationReplacements(): array
    {
        return $this->translationReplacements;
    }

    /** @param array<string, string> $replacements */
    protected function withTranslationReplacements(array $replacements): static
    {
        $this->translationReplacements = $replacements;

        return $this;
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
        $types = (array) trans('core.model_types');

        return __('core.model_context', [
            'type' => Arr::string($types, $model->getMorphClass()),
            'name' => self::nameOf($model),
        ]);
    }

    /**
     * The name a person reads for a record: `name`, else `full_name` (referees and managers), else its key
     * (empty for a record not saved yet).
     */
    private static function nameOf(Model $model): string
    {
        foreach (['name', 'full_name'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value)) {
                return $value;
            }
        }

        $key = $model->getKey();

        return is_int($key) || is_string($key) ? (string) $key : '';
    }
}
