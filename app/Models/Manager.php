<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ManagerQueryBuilder;
use App\Enums\ManagerStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\HasEmployments;
use App\Models\Concerns\HasFullName;
use App\Models\Concerns\Manageables;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements CanBeAStableMember, Employable
{
    use CanJoinStables;
    use HasFactory;
    use HasFullName;
    use Manageables;
    use OwnedByUser;
    use SoftDeletes;
    use HasEmployments;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id', 'first_name', 'last_name', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\ManagerQueryBuilder<Manager>
     */
    public function newEloquentBuilder($query): ManagerQueryBuilder
    {
        return new ManagerQueryBuilder($query);
    }

    /**
     * Determine if the manager is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        return $this->isAvailable() || $this->isInjured();
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        return $this->isRetired();
    }
}
