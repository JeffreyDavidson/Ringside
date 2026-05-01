<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake suspension model for testing IsSuspendable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_suspensions')]
#[Fillable('entity_id', 'suspended_at', 'reinstated_at')]
class FakeSuspensionModel extends Model
{
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'reinstated_at' => 'datetime',
        ];
    }
}
