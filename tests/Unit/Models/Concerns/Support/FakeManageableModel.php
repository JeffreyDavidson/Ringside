<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanBeManaged;
use App\Models\Contracts\Manageable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake model for testing CanBeManaged trait in isolation.
 *
 * @implements Manageable<FakeManagerPivotModel, self>
 */
#[Table('fake_manageable_models')]
#[Fillable('name')]
class FakeManageableModel extends Model implements Manageable
{
    /** @use CanBeManaged<FakeManagerPivotModel, self> */
    use CanBeManaged;

    protected function managerAssignmentTable(): string
    {
        return (new FakeManagerPivotModel())->getTable();
    }

    protected function managerAssignmentPivotModel(): string
    {
        return FakeManagerPivotModel::class;
    }
}
