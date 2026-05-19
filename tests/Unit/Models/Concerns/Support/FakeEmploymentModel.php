<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake employment model for testing IsEmployable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_employments')]
#[Fillable('entity_id', 'started_at', 'ended_at')]
class FakeEmploymentModel extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
