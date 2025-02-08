<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TFactory|null $use_factory
 *
 * @method static \Database\Factories\MatchDecisionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision query()
 *
 * @mixin \Eloquent
 */
class MatchDecision extends Model
{
    /** @use HasFactory<\Database\Factories\MatchDecisionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];
}
