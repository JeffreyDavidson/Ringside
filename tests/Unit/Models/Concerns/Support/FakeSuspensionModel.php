<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Fake suspension model for testing IsSuspendable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeSuspensionModel extends Model
{
    protected $table = 'fake_suspensions';

    protected $fillable = [
        'entity_id',
        'suspended_at',
        'reinstated_at',
    ];

    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'reinstated_at' => 'datetime',
        ];
    }
}
