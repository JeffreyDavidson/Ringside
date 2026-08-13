<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use LogicException;

/**
 * Provides a default implementation of getDisplayName() based on common model conventions.
 */
trait ProvidesDisplayName
{
    /**
     * Laravel boot hook for this trait.
     */
    public static function bootProvidesDisplayName(): void
    {
        static::retrieved(function ($model) {
            if (! in_array('display_name', $model->appends, true)) {
                $model->appends[] = 'display_name';
            }
        });
    }

    /**
     * Guess and return a display-friendly name for the model.
     *
     * @throws LogicException if no usable fields are found.
     */
    public function getDisplayName(): string
    {
        $attributes = array_merge(get_object_vars($this), $this->getAttributes());

        if (! empty($attributes['name'])) {
            return (string) $attributes['name'];
        }

        if (! empty($attributes['full_name'])) {
            return (string) $attributes['full_name'];
        }

        if (array_key_exists('first_name', $attributes) && array_key_exists('last_name', $attributes)) {
            $firstName = $attributes['first_name'];
            $lastName = $attributes['last_name'];
            $displayName = mb_trim((string) ($firstName ?? '').' '.(string) ($lastName ?? ''));

            if ($displayName !== '') {
                return $displayName;
            }
        }

        throw new LogicException(sprintf(
            'Model [%s] does not define a display name field.',
            static::class
        ));
    }

    /**
     * Laravel accessor for display name.
     *
     * @return Attribute<string, never>
     */
    public function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->getDisplayName());
    }
}
