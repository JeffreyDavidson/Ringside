<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Employment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employable_id',
        'employable_type',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the employed model.
     */
    public function employable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if employment start date was started before a given date.
     */
    public function startedBefore(Carbon $date): bool
    {
        return $this->started_at->lte($date);
    }

    /**
     * Determine if employment start date was started after a given date.
     */
    public function startedAfter(Carbon $date): bool
    {
        return $this->started_at->gt($date);
    }
}
