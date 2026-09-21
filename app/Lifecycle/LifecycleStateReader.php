<?php

declare(strict_types=1);

namespace App\Lifecycle;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class LifecycleStateReader
{
    /**
     * Read a group of projected lifecycle booleans, falling back to their
     * relationship checks when the model was not loaded with those projections.
     *
     * @param  array<string, array{attribute: string, fallback: Closure}>  $projections
     * @return array<string, bool>
     */
    public static function readProjectedBooleans(Model $model, array $projections): array
    {
        return array_map(
            fn (array $projection): bool => self::readProjectedBoolean(
                $model,
                $projection['attribute'],
                $projection['fallback'],
            ),
            $projections,
        );
    }

    public static function readProjectedBoolean(Model $model, string $attribute, Closure $fallback): bool
    {
        $attributes = $model->getAttributes();

        return array_key_exists($attribute, $attributes)
            ? (bool) $attributes[$attribute]
            : $fallback();
    }
}
