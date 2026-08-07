<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake retirable model for testing IsRetirable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_retirables')]
#[Fillable('name')]
class FakeRetirableModel extends Model
{
    use IsRetirable;

    protected function resolveRetirementModelClass(): string
    {
        return FakeRetirementModel::class;
    }
}
