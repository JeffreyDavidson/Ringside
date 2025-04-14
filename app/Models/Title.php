<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleBuilder;
use App\Enums\ActivationStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property ActivationStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleActivation> $activations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleChampionship> $championships
 * @property-read TitleActivation|null $currentActivation
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read TitleRetirement|null $currentRetirement
 * @property-read TitleActivation|null $firstActivation
 * @property-read TitleActivation|null $futureActivation
 * @property-read TFactory|null $use_factory
 * @property-read TitleActivation|null $previousActivation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleActivation> $previousActivations
 * @property-read TitleRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TitleRetirement> $retirements
 *
 * @method static \Database\Factories\TitleFactory factory($count = null, $state = [])
 * @method static \App\Builders\TitleBuilder newModelQuery()
 * @method static \App\Builders\TitleBuilder newQuery()
 * @method static \App\Builders\TitleBuilder query()
 * @method static \App\Builders\TitleBuilder active()
 * @method static \App\Builders\TitleBuilder competable()
 * @method static \App\Builders\TitleBuilder inactive()
 * @method static \App\Builders\TitleBuilder retired()
 * @method static \App\Builders\TitleBuilder withFutureActivation()
 * @method static \App\Builders\TitleBuilder onlyTrashed()
 * @method static \App\Builders\TitleBuilder withTrashed()
 * @method static \App\Builders\TitleBuilder withoutTrashed()
 *
 * @mixin \Eloquent
 */
final class Title extends Model implements Activatable, Retirable
{
    use Concerns\HasChampionships;
    use Concerns\IsActivatable;
    use Concerns\IsRetirable;

    /** @use HasBuilder<TitleBuilder> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\TitleFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
        'status' => ActivationStatus::Unactivated->value,
    ];

    protected static string $builder = TitleBuilder::class;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
        ];
    }

    /**
     * @return HasMany<TitleActivation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(TitleActivation::class);
    }

    /**
     * @return HasMany<TitleRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(TitleRetirement::class);
    }

    /**
     * Retrieve the readable name of the model.
     */
    public function getNameLabel(): string
    {
        return $this->name;
    }
}
