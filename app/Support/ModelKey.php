<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use LogicException;

final class ModelKey
{
    /**
     * Narrow Eloquent's untyped primary key to the scalar types keys can have.
     */
    public static function of(Model $model): int|string
    {
        $key = $model->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new LogicException(class_basename($model).' requires a persisted integer or string key.');
        }

        return $key;
    }
}
