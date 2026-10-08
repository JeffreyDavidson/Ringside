<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

/**
 * Stores one assignment row of a history table's pivot table.
 */
function recordAssignment(
    string $pivot,
    string $parentColumn,
    string $childColumn,
    Model $parent,
    Model $child,
    CarbonInterface $hiredAt,
    ?CarbonInterface $firedAt,
): void {
    $pivot::query()->create([
        $parentColumn => $parent->getKey(),
        $childColumn => $child->getKey(),
        'hired_at' => $hiredAt,
        'fired_at' => $firedAt,
    ]);
}

describe('assignment history tables', function (): void {
    it('returns only ended assignments for the requested record in newest-first order', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        $recentChild = $createChild('Recent Child');
        $olderChild = $createChild('Older Child');
        $currentChild = $createChild('Current Child');
        $otherChild = $createChild('Other Child');

        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $olderChild, Date::now()->subMonths(3), Date::now()->subMonths(2));
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $recentChild, Date::now()->subMonth(), Date::now()->subWeek());
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $currentChild, Date::now()->subDays(3), null);
        recordAssignment($pivot, $parentColumn, $childColumn, $createParent(), $otherChild, Date::now()->subDays(2), Date::now()->subDay());
        $table = app($component);
        $table->{$parameter} = $parent->getKey();

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments->pluck($childColumn)->all())->toBe([
            $recentChild->getKey(),
            $olderChild->getKey(),
        ])->and($assignments->every->relationLoaded($childRelation))->toBeTrue();
    })->with('livewire assignment history tables');

    it('keeps separate historical assignments for a returning record', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        $child = $createChild('Returning Child');
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $child, Date::now()->subMonths(4), Date::now()->subMonths(3));
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $child, Date::now()->subMonths(2), Date::now()->subMonth());
        $table = app($component);
        $table->{$parameter} = $parent->getKey();

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toHaveCount(2)
            ->and($assignments->pluck($childColumn)->all())->toBe([
                $child->getKey(),
                $child->getKey(),
            ]);
    })->with('livewire assignment history tables');

    it('omits deleted records', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        $child = $createChild('Deleted Child');
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $child, Date::now()->subMonth(), Date::now()->subWeek());
        $child->delete();
        $table = app($component);
        $table->{$parameter} = $parent->getKey();

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toBeEmpty();
    })->with('livewire assignment history tables');

    it('resolves eager-loaded records without additional queries', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        $child = $createChild('Resolved Child');
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $child, Date::now()->subYear(), Date::now()->subMonth());
        $table = app($component);
        $table->{$parameter} = $parent->getKey();
        $assignment = $table->builder()->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $renderedChild = $table->columns()[0]->resolveValue($assignment);

        // Assert
        expect($renderedChild)->toBe('Resolved Child')
            ->and($assignment->relationLoaded($childRelation))->toBeTrue()
            ->and(DB::getQueryLog())->toBeEmpty();
    })->with('livewire assignment history tables');

    it('renders previous record names, dates, and search controls', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        $previousChild = $createChild('Previous Child');
        $currentChild = $createChild('Current Child');
        $hiredAt = Date::now()->subMonth();
        $firedAt = Date::now()->subWeek();
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $previousChild, $hiredAt, $firedAt);
        recordAssignment($pivot, $parentColumn, $childColumn, $parent, $currentChild, Date::now()->subDay(), null);

        // Act
        $table = livewire($component, [$parameter => $parent->getKey()]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml("placeholder=\"{$placeholder}\"")
            ->assertSee('Previous Child')
            ->assertSee($hiredAt->format('Y-m-d'))
            ->assertSee($firedAt->format('Y-m-d'))
            ->assertDontSee('Current Child');
    })->with('livewire assignment history tables');

    it('searches previous records by name', function (
        string $search,
        string $visibleName,
        string $hiddenName,
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        string $pivot,
        string $parentColumn,
        string $childColumn,
        string $childRelation,
        string $placeholder,
    ): void {
        // Arrange
        $parent = $createParent();
        foreach (['Historic Manager', 'Former Advisor'] as $offset => $name) {
            recordAssignment(
                $pivot,
                $parentColumn,
                $childColumn,
                $parent,
                $createChild($name),
                Date::now()->subMonths($offset + 3),
                Date::now()->subMonths($offset + 1),
            );
        }

        // Act
        $table = livewire($component, [$parameter => $parent->getKey()]);
        $table->set('search', $search);

        // Assert
        $table
            ->assertSee($visibleName)
            ->assertDontSee($hiddenName);
    })->with([
        'first name' => ['Historic', 'Historic Manager', 'Former Advisor'],
        'last name' => ['Advisor', 'Former Advisor', 'Historic Manager'],
        'full name' => ['Historic Manager', 'Historic Manager', 'Former Advisor'],
    ])->with('livewire assignment history tables');
});
