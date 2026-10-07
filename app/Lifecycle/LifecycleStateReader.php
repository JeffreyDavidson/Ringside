<?php

declare(strict_types=1);

namespace App\Lifecycle;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class LifecycleStateReader
{
    public static function readProjectedBoolean(Model $model, string $attribute, Closure $fallback): bool
    {
        $attributes = $model->getAttributes();

        return array_key_exists($attribute, $attributes)
            ? (bool) $attributes[$attribute]
            : $fallback();
    }
}
