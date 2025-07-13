<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Fake retirement model for testing IsRetirable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeRetirementModel extends Model
{
    protected $table = 'fake_retirements';

    protected $fillable = [
        'entity_id',
        'retired_at',
        'unretired_at',
    ];

    protected function casts(): array
    {
        return [
            'retired_at' => 'datetime',
            'unretired_at' => 'datetime',
        ];
    }
}
