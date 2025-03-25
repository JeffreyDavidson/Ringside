<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember query()
 *
 * @mixin \Eloquent
 */
class StableMember extends MorphPivot
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'member_id',
        'member_type',
        'joined_at',
        'left_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
