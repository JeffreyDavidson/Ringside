<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('membership history tables', function (): void {
    it('renders previous record names, membership dates, and search controls', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        Closure $link,
        string $placeholder,
        ?string $linkRoute,
    ): void {
        // Arrange
        $parent = $createParent();
        $formerChild = $createChild('Former Record');
        $currentChild = $createChild('Current Record');
        $joinedAt = Date::now()->subMonths(3);
        $leftAt = Date::now()->subMonth();
        $link($parent, $formerChild, $joinedAt, $leftAt);
        $link($parent, $currentChild, Date::now()->subWeek(), null);

        // Act
        $table = livewire($component, [$parameter => $parent->getKey()]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml("placeholder=\"{$placeholder}\"")
            ->assertSee('Former Record')
            ->assertSee($joinedAt->format('Y-m-d'))
            ->assertSee($leftAt->format('Y-m-d'))
            ->assertDontSee('Current Record');

        if ($linkRoute !== null) {
            $table->assertSeeHtml(route($linkRoute, $formerChild));
        }
    })->with('livewire membership history tables');

    it('searches previous records by name', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        Closure $link,
        string $placeholder,
        ?string $linkRoute,
    ): void {
        // Arrange
        $parent = $createParent();
        foreach (['Historic Record', 'Former Record'] as $offset => $name) {
            $link(
                $parent,
                $createChild($name),
                Date::now()->subMonths($offset + 3),
                Date::now()->subMonths($offset + 1),
            );
        }

        // Act
        $table = livewire($component, [$parameter => $parent->getKey()]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Record')
            ->assertDontSee('Former Record');
    })->with('livewire membership history tables');

    it('renders an unknown record when the related record was deleted', function (
        string $component,
        string $parameter,
        Closure $createParent,
        Closure $createChild,
        Closure $link,
    ): void {
        // Arrange
        $parent = $createParent();
        $child = $createChild('Deleted Record');
        $link($parent, $child, Date::now()->subMonth(), Date::now()->subWeek());
        $child->delete();

        // Act
        $table = livewire($component, [$parameter => $parent->getKey()]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('Unknown');
    })->with('livewire membership history tables that name deleted records unknown');
});
