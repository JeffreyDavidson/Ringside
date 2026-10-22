<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Shared\BusinessRuleReason;
use App\Support\ModelName;
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
        return __('core.model_context', [
            'type' => __("core.model_types.{$model->getMorphClass()}"),
            'name' => ModelName::of($model),
        ]);
    }
}
