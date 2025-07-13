<?php

declare(strict_types=1);

/**
 * Trait Test for BelongsToUser
 *
 * This test ensures the BelongsToUser trait provides a consistent User relationship
 * for any model that needs to belong to a user.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\BelongsToUser;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

describe('BelongsToUser Trait Unit Tests', function () {
    describe('user relationship', function () {
        test('provides user relationship', function () {
            $model = new class extends Model
            {
                use BelongsToUser;
            };

            expect($model->user())->toBeInstanceOf(BelongsTo::class);
        });

        test('user relationship uses correct foreign key', function () {
            $model = new class extends Model
            {
                use BelongsToUser;
            };

            $relation = $model->user();
            expect($relation->getForeignKeyName())->toBe('user_id');
        });

        test('user relationship uses correct related model', function () {
            $model = new class extends Model
            {
                use BelongsToUser;
            };

            $relation = $model->user();
            expect($relation->getRelated())->toBeInstanceOf(User::class);
        });

        test('user relationship returns BelongsTo type', function () {
            $model = new class extends Model
            {
                use BelongsToUser;
            };

            $relation = $model->user();
            expect($relation)->toBeInstanceOf(BelongsTo::class);
        });
    });
});
