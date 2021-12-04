<?php

namespace App\Models;

use App\Enums\ManagerStatus;
use App\Models\Contracts\StableMember;
use App\Observers\ManagerObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements StableMember
{
    use Concerns\HasFullName,
        Concerns\Manageables,
        Concerns\OwnedByUser,
        Concerns\StableMember,
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

        self::observe(ManagerObserver::class);
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'managers';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    public function isAvailable()
    {
        return $this->currentEmployment()->exists();
    }
}
