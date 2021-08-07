<?php

namespace App\Models\Contracts;

interface Deactivatable
{
    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isDeactivated();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeDeactivated($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithLastDeactivationDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByLastDeactivationDate($query);
}
