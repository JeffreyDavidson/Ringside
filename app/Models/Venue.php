<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the full address of the venue.
     */
    public function getFullAddressAttribute()
    {
        return $this->address1 .' '. $this->address2 .'<br>'. $this->city . ', '. $this->state . ' '. $this->zip;
    }
}
