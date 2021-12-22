<?php

namespace App\Models;

use App\Casts\HeightCast;
use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\StableMember;
use App\Models\Contracts\TagTeamMember;
use App\Observers\WrestlerObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends SingleRosterMember implements Bookable, Manageable, StableMember, TagTeamMember
{
    use Concerns\Bookable,
        Concerns\Manageable,
        Concerns\OwnedByUser,
        Concerns\StableMember,
        Concerns\TagTeamMember,
        Concerns\Unguarded,
        HasFactory,
        SoftDeletes;

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(WrestlerObserver::class);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => WrestlerStatus::class,
        'height' => HeightCast::class,
    ];
}
