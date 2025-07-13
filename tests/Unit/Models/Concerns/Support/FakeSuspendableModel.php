<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsSuspendable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake suspendable model for testing IsSuspendable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeSuspendableModel extends Model
{
    use IsSuspendable;

    protected $table = 'fake_suspendables';

    protected $fillable = ['name'];

    /**
     * Static method to override suspension model class for testing.
     */
    public static function fakeSuspensionModel(?string $modelClass): void
    {
        static::$fakeSuspensionModelClass = $modelClass;
    }

    private static ?string $fakeSuspensionModelClass = null;

    public function resolveSuspensionModelClass(): string
    {
        return static::$fakeSuspensionModelClass ?? FakeSuspensionModel::class;
    }

    protected function resolveRelatedModelClass(string $suffix): string
    {
        if ($suffix === 'Suspension') {
            return $this->resolveSuspensionModelClass();
        }

        return parent::resolveRelatedModelClass($suffix);
    }
}
