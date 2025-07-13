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
        if ((array_key_exists('name', $this->getAttributes()) || property_exists($this, 'name')) && $this->name) {
            return $this->name;
        }

        if ((array_key_exists('full_name', $this->getAttributes()) || property_exists($this, 'full_name')) && $this->full_name) {
            return $this->full_name;
        }

        if ((array_key_exists('first_name', $this->getAttributes()) || property_exists($this, 'first_name')) &&
            (array_key_exists('last_name', $this->getAttributes()) || property_exists($this, 'last_name')) &&
            ($this->first_name !== null || $this->last_name !== null)) {
            return mb_trim("{$this->first_name} {$this->last_name}");
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
