<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $number_of_sides
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TFactory|null $use_factory
 *
 * @method static \Database\Factories\MatchTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType query()
 *
 * @mixin \Eloquent
 */
class MatchType extends Model
{
    /** @use HasFactory<\Database\Factories\MatchTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'number_of_sides',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number_of_sides' => 'integer',
        ];
    }
}
