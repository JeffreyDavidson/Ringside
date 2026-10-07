<?php

declare(strict_types=1);

use App\Models\Events\Venue;
use Illuminate\Support\Facades\Blade;

use function Pest\Laravel\actingAs;

function renderEntityActions(Venue $venue, string $attributes = ''): string
{
    return Blade::render(
        '<x-tables.entity-actions :model="$venue" :name="$venue->name" menu-label="Venue actions" :show-url="route(\'venues.show\', $venue)" form-modal="venues.modals.form-modal" '.$attributes.'>Extra</x-tables.entity-actions>',
        ['venue' => $venue],
    );
}

describe('entity actions menu', function (): void {
    it('renders view, edit and remove entries for an administrator', function (): void {
        $venue = Venue::factory()->create();
        actingAs(administrator());

        $html = renderEntityActions($venue);

        expect($html)
            ->toContain('Actions for '.e($venue->name))
            ->toContain('Venue actions')
            ->toContain(route('venues.show', $venue))
            ->toContain('venues.modals.form-modal')
            ->toContain("delete({$venue->id})")
            ->toContain('View')
            ->toContain('Edit')
            ->toContain('Remove')
            ->toContain('Extra');
    });

    it('omits the remove entry when the model is not removable', function (): void {
        $venue = Venue::factory()->create();
        actingAs(administrator());

        expect(renderEntityActions($venue, ':removable="false"'))
            ->toContain('Edit')
            ->not->toContain('Remove');
    });

    it('hides edit and remove from users without permission', function (): void {
        $venue = Venue::factory()->create();
        actingAs(basicUser());

        expect(renderEntityActions($venue))
            ->not->toContain('Edit')
            ->not->toContain('Remove');
    });
});
