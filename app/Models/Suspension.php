<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suspension extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['suspendable_id', 'suspendable_type', 'started_at', 'ended_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Retrieve the suspended model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function suspendable()
    {
        return $this->morphTo();
    }
}
