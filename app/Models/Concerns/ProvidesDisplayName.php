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
        if ((array_key_exists('name', $this->getAttributes()) || property_exists($this, 'name')) && ! empty($this->name)) {
            return $this->name;
        }

        if ((array_key_exists('full_name', $this->getAttributes()) || property_exists($this, 'full_name')) && ! empty($this->full_name)) {
            return $this->full_name;
        }

        $hasFirstName = array_key_exists('first_name', $this->getAttributes()) || property_exists($this, 'first_name');
        $hasLastName = array_key_exists('last_name', $this->getAttributes()) || property_exists($this, 'last_name');
        if ($hasFirstName && $hasLastName) {
            $firstName = $this->first_name;
            $lastName = $this->last_name;

            // Both null → no display name available, fall through to exception.
            if ($firstName !== null || $lastName !== null) {
                return mb_trim(($firstName ?? '').' '.($lastName ?? ''));
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
