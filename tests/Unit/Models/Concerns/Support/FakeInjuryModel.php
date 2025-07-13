<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Fake injury model for testing IsInjurable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeInjuryModel extends Model
{
    protected $table = 'fake_injuries';

    protected $fillable = [
        'entity_id',
        'injured_at',
        'healed_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'injured_at' => 'datetime',
            'healed_at' => 'datetime',
        ];
    }
}
