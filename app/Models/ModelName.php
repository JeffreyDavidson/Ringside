<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ModelName
{
    /**
     * The name a person reads for a record: `name`, else `full_name` (referees and managers), else its key (empty for a record not saved yet).
     */
    public static function of(Model $model): string
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
