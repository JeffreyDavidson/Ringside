<?php

namespace App\Models;

use App\Eloquent\Concerns\HasCustomRelationships;
use App\Enums\WrestlerStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends SingleRosterMember
{
    use SoftDeletes,
        HasCustomRelationships,
        Concerns\HasAHeight,
        Concerns\CanBeStableMember,
        Concerns\CanBeTagTeamPartner,
        Concerns\CanBeBooked,
        Concerns\Unguarded;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => WrestlerStatus::class,
    ];

    /**
     * Get the user assigned to the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
