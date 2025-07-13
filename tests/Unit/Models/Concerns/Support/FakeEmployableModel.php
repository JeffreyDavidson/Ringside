<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsEmployable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake employable model for testing IsEmployable trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeEmployableModel extends Model
{
    use IsEmployable;

    protected $table = 'fake_employables';

    protected $fillable = ['name'];

    /**
     * Static method to override employment model class for testing.
     */
    public static function fakeEmploymentModel(?string $modelClass): void
    {
        static::$fakeEmploymentModelClass = $modelClass;
    }

    private static ?string $fakeEmploymentModelClass = null;

    public function resolveEmploymentModelClass(): string
    {
        return static::$fakeEmploymentModelClass ?? FakeEmploymentModel::class;
    }

    protected function resolveRelatedModelClass(string $suffix): string
    {
        if ($suffix === 'Employment') {
            return $this->resolveEmploymentModelClass();
        }

        return parent::resolveRelatedModelClass($suffix);
    }
}
