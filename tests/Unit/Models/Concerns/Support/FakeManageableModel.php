<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanBeManaged;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake model for testing CanBeManaged trait in isolation.
 */
#[Table('fake_manageable_models')]
#[Fillable('name')]
class FakeManageableModel extends Model
{
    use CanBeManaged;

    public function resolveManagersPivotModel(): string
    {
        return FakeManagerPivotModel::class;
    }
}
