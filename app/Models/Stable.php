<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\StableBuilder;
use App\Enums\StableStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model implements Activatable, Retirable
{
    use Concerns\HasActivations;
    use Concerns\HasMembers;
    use Concerns\HasRetirements;
    use Concerns\OwnedByUser;
    use HasFactory;
    use SoftDeletes;

    /**
     * The minimum number of members allowed on a tag team.
     */
    public const MIN_MEMBERS_COUNT = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => StableStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return StableBuilder<Stable>
     */
    public function newEloquentBuilder($query): StableBuilder // @pest-ignore-type
    {
        return new StableBuilder($query);
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }
}
