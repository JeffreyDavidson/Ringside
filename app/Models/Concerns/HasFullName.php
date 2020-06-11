<?php

namespace App\Models\Concerns;

trait HasFullName
{
    /**
     * Get the full name of the model.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
