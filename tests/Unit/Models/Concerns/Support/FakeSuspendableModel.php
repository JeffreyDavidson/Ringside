<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake suspendable model for testing IsSuspendable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 *
 * @implements Suspendable<FakeSuspensionModel, self>
 */
#[Table('fake_suspendables')]
#[Fillable('name')]
class FakeSuspendableModel extends Model implements Suspendable
{
    /** @use IsSuspendable<FakeSuspensionModel, self> */
    use IsSuspendable;

    protected function resolveSuspensionModelClass(): string
    {
        return FakeSuspensionModel::class;
    }
}
