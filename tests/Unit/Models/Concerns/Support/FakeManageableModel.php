<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanBeManaged;
use App\Models\Contracts\Manageable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake model for testing CanBeManaged trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 *
 * @implements Manageable<FakeManagerPivotModel, static>
 */
class FakeManageableModel extends Model implements Manageable
{
    use CanBeManaged;

    protected $table = 'fake_manageables';

    protected $fillable = ['name'];

    public function resolveManagersPivotModel(): string
    {
        return FakeManagerPivotModel::class;
    }
}
