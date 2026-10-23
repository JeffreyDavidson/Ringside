<?php

declare(strict_types=1);

namespace App\Exceptions\Lifecycle;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

final class EmploymentDateException extends BaseBusinessException
{
    public static function currentStartLocked(Model $employable): static
    {
        return new self(__('core.errors.employment_date.current_start_locked', ['context' => self::formatModelContext($employable)]));
    }

    public static function scheduledInThePast(Model $employable): static
    {
        return new self(__('core.errors.employment_date.scheduled_in_past', ['context' => self::formatModelContext($employable)]));
    }
}
