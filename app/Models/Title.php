<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleBuilder;
use App\Enums\TitleStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model implements Activatable, Retirable
{
    use Concerns\HasActivations;
    use Concerns\HasChampionships;
    use Concerns\HasRetirements;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TitleStatus::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => TitleStatus::Unactivated->value,
    ];

    public static function query(): TitleBuilder
    {
        return parent::query();
    }

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): TitleBuilder
    {
        return new TitleBuilder($query);
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }
}
