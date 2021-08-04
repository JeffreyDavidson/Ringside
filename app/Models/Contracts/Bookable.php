<?php

namespace App\Models\Contracts;

interface Bookable
{
    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isBookable();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeBookable($query);
}
