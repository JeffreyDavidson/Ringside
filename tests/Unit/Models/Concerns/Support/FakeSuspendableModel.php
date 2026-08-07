<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsSuspendable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake suspendable model for testing IsSuspendable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_suspendables')]
#[Fillable('name')]
class FakeSuspendableModel extends Model
{
    use IsSuspendable;

    protected function resolveSuspensionModelClass(): string
    {
        return FakeSuspensionModel::class;
    }
}
