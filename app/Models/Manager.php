<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ManagerQueryBuilder;
use App\Enums\ManagerStatus;
use App\Models\Contracts\CanBeAStableMember;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements CanBeAStableMember
{
    use Concerns\CanJoinStables;
    use Concerns\Manageables;
    use Concerns\OwnedByUser;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => ManagerStatus::UNEMPLOYED->value,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): ManagerQueryBuilder
    {
        return new ManagerQueryBuilder($query);
    }

    /**
     * Determine if the manager is available to manager manageables.
     */
    public function isAvailable(): bool
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Get the display name of the manager.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
