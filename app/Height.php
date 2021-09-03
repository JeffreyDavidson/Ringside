<?php

namespace App;

class Height
{
    public $height;

    public function __construct($height)
    {
        $this->height = $height;
    }

    /**
     * Return the wrestler's height formatted.
     *
     * @return string
     */
    public function getFormattedHeightAttribute()
    {
        return "{$this->feet}'{$this->inches}\"";
    }

    /**
     * Return the wrestler's height in feet.
     *
     * @return string
     */
    public function feet()
    {
        return intval($this->height / 12);
    }

    /**
     * Return the wrestler's height in inches.
     *
     * @return string
     */
    public function inches()
    {
        return $this->height % 12;
    }

    /**
     * Return the wrestler's height in inches.
     *
     * @return string
     */
    public function inInches()
    {
        return $this->height;
    }

    public function __toString()
    {
        return strval($this->height);
    }
}
