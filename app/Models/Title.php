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
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => TitleStatus::Unactivated->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TitleStatus::class,
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return TitleBuilder<Title>
     */
    public function newEloquentBuilder($query): TitleBuilder // @pest-ignore-type
    {
        return new TitleBuilder($query);
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }
}
