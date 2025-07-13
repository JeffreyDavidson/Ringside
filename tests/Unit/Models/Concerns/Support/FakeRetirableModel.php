<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake retirable model for testing IsRetirable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeRetirableModel extends Model
{
    use IsRetirable;

    protected $table = 'fake_retirables';

    protected $fillable = ['name'];

    /**
     * Static method to override retirement model class for testing.
     */
    public static function fakeRetirementModel(?string $modelClass): void
    {
        static::$fakeRetirementModelClass = $modelClass;
    }

    private static ?string $fakeRetirementModelClass = null;

    public function resolveRetirementModelClass(): string
    {
        return static::$fakeRetirementModelClass ?? FakeRetirementModel::class;
    }

    protected function resolveRelatedModelClass(string $suffix): string
    {
        if ($suffix === 'Retirement') {
            return $this->resolveRetirementModelClass();
        }

        return parent::resolveRelatedModelClass($suffix);
    }
}
