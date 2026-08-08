<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake retirable model for testing IsRetirable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 *
 * @implements Retirable<FakeRetirementModel, self>
 */
#[Table('fake_retirables')]
#[Fillable('name')]
class FakeRetirableModel extends Model implements Retirable
{
    /** @use IsRetirable<FakeRetirementModel, self> */
    use IsRetirable;

    protected function resolveRetirementModelClass(): string
    {
        return FakeRetirementModel::class;
    }
}
