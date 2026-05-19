<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Fake pivot model for testing CanBeManaged trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeManagerPivotModel extends Pivot
{
    protected $table = 'fake_managers';

    protected $fillable = [
        'entity_id',
        'manager_id',
        'hired_at',
        'fired_at',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'datetime',
            'fired_at' => 'datetime',
        ];
    }
}
