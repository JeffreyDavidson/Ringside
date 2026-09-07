<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

describe('select filter configuration', function (): void {
    test('select filter factory creates a configured filter', function (): void {
        // Arrange
        $options = ['active' => 'Active'];

        // Act
        $filter = SelectFilter::make('Status');
        $filter->options($options);

        // Assert
        expect($filter->getKey())->toBe('status')
            ->and($filter->getOptions())->toBe(['active' => 'Active'])
            ->and($filter->getDefaultValue())->toBe('');
    });
});

describe('select filter application', function (): void {
    test('empty selections leave queries unchanged without invoking callbacks', function (?string $selection): void {
        // Arrange
        $user = User::factory()->create();
        $query = (new User())->newQuery();
        $filter = SelectFilter::make('Role')->filter(
            fn (): never => throw new LogicException('Empty selections must not invoke the callback.'),
        );

        // Act
        $filter->apply($query, $selection);
        $users = $query->get();

        // Assert
        expect($users->modelKeys())->toBe([$user->id]);
    })->with([
        'null' => [null],
        'empty string' => [''],
    ]);

    test('selected values constrain results through the callback', function (Role $role): void {
        // Arrange
        $selected = User::factory()->create(['role' => $role]);
        User::factory()->create([
            'role' => $role === Role::Administrator ? Role::Basic : Role::Administrator,
        ]);
        $query = (new User())->newQuery();
        $filter = SelectFilter::make('Role')->filter(
            /** @param Builder<User> $query */
            function (Builder $query, string $value): void {
                $query->where('role', $value);
            },
        );

        // Act
        $filter->apply($query, $role->value);
        $users = $query->get();

        // Assert
        expect($users->modelKeys())->toBe([$selected->id]);
    })->with([
        'administrator' => [Role::Administrator],
        'basic user' => [Role::Basic],
    ]);

    test('selections leave queries unchanged without a callback', function (): void {
        // Arrange
        $user = User::factory()->basicUser()->create();
        $query = (new User())->newQuery();
        $filter = SelectFilter::make('Role');

        // Act
        $filter->apply($query, Role::Administrator->value);
        $users = $query->get();

        // Assert
        expect($users->modelKeys())->toBe([$user->id]);
    });
});
