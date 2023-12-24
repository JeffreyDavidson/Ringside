<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ManagerBuilder;
use App\Enums\ManagerStatus;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model implements CanBeAStableMember, Employable, Injurable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;
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
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => ManagerStatus::Unemployed->value,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return ManagerBuilder<Manager>
     */
    public function newEloquentBuilder($query): ManagerBuilder
    {
        return new ManagerBuilder($query);
    }

    /**
     * Determine if the manager is available to manager manageables.
     */
    public function isAvailable(): bool
    {
        return $this->status->label() === ManagerStatus::Available->label();
    }

    /**
     * Determine if the model can be retired.
     */
    public function canBeRetired(): bool
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Get the identifier of the manager.
     */
    public function getIdentifier(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the manager's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
