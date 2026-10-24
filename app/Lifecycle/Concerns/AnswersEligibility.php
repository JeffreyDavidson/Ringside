<?php

declare(strict_types=1);

namespace App\Lifecycle\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

/**
 * Turns an `ensureCan…()` guard into the yes/no answer a `can…()` method gives.
 */
trait AnswersEligibility
{
    /**
     * Answer false when the guard throws the given refusal, true when it does
     * not throw. Any other business exception, or any other exception, propagates.
     *
     * @param  Closure(): mixed  $ensure
     * @param  class-string<BaseBusinessException>  $refusal
     */
    protected function passes(Closure $ensure, string $refusal): bool
    {
        try {
            $ensure();

            return true;
        } catch (BaseBusinessException $exception) {
            if ($exception instanceof $refusal) {
                return false;
            }

            throw $exception;
        }
    }
}
