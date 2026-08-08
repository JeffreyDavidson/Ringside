<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsEmployable;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake employable model for testing IsEmployable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_employables')]
#[Fillable('name')]
class FakeEmployableModel extends Model implements Employable
{
    use IsEmployable;

    protected function resolveEmploymentModelClass(): string
    {
        return FakeEmploymentModel::class;
    }
}
