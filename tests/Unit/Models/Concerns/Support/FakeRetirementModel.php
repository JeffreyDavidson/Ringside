<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake retirement model for testing IsRetirable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_retirements')]
#[Fillable('entity_id', 'retired_at', 'unretired_at')]
class FakeRetirementModel extends Model
{
    protected function casts(): array
    {
        return [
            'retired_at' => 'datetime',
            'unretired_at' => 'datetime',
        ];
    }
}
