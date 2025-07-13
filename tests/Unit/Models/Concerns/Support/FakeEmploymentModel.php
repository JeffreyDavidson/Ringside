<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Fake employment model for testing IsEmployable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeEmploymentModel extends Model
{
    protected $table = 'fake_employments';

    protected $fillable = [
        'entity_id',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
