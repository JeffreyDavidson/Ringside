<?php

namespace App\Models\Concerns;

trait HasAHeight
{
    /**
     * Return the model's height formatted.
     *
     * @return string
     */
    public function getFormattedHeightAttribute()
    {
        return "{$this->feet}'{$this->inches}\"";
    }

    /**
     * Return the model's height in feet.
     *
     * @return string
     */
    public function getFeetAttribute()
    {
        return floor($this->height / 12);
    }

    /**
     * Return the model's height in inches.
     *
     * @return string
     */
    public function getInchesAttribute()
    {
        return $this->height % 12;
    }
}
