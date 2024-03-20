<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Activation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'activatable_id',
        'activatable_type',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the activated model.
     *
     * @return MorphTo<Model, Activation>
     */
    public function activatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine an activation started before a given date.
     */
    public function startedBefore(Carbon $date): bool
    {
        return $this->started_at->lt($date);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
