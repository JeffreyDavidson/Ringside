<?php

namespace App\Models;

use App\Builders\TitleQueryBuilder;
use App\Enums\TitleStatus;
use App\Models\Concerns\Activations;
use App\Models\Concerns\Competable;
use App\Models\Concerns\Deactivations;
use App\Models\Concerns\Retirements;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use App\Observers\TitleObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model implements Activatable, Deactivatable, Retirable
{
    use Activations,
        Competable,
        Deactivations,
        HasFactory,
        Retirements,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['name', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TitleStatus::class,
    ];

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(TitleObserver::class);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \App\Builders\TitleQueryBuilder<\App\Models\Title>
     */
    public function newEloquentBuilder($query): TitleQueryBuilder
    {
        return new TitleQueryBuilder($query);
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isCurrentlyActivated()) {
            return true;
        }

        if ($this->isDeactivated()) {
            return true;
        }

        return false;
    }
}
