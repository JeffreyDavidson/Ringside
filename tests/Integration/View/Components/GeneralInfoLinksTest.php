<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

describe('general info links', function (): void {
    it('renders route links as high-contrast underlined text', function (): void {
        $html = Blade::render('<x-route-link route="/venues/1" label="Riverside Armory" />');

        expect($html)
            ->toContain('href="/venues/1"')
            ->toContain('Riverside Armory')
            ->toContain('text-ringside-ink')
            ->toContain('underline')
            ->toContain('focus-visible:outline-ringside-white')
            ->not->toContain('text-gray-800');
    });

    it('renders link rows with the same label and value colours as stat rows', function (string $component): void {
        $html = Blade::render('<x-card.general-info.'.$component.' label="Venue">Riverside Armory</x-card.general-info.'.$component.'>');

        expect($html)
            ->toContain('Venue:')
            ->toContain('text-ringside-muted')
            ->toContain('text-ringside-ink')
            ->not->toContain('text-gray-');
    })->with(['links', 'link-list']);
});

describe('general info card', function (): void {
    it('renders as a square panel on the Ringside surface', function (): void {
        $html = Blade::render('<x-card.general-info><x-card.general-info.stat label="Status" value="Employed" /></x-card.general-info>');

        expect($html)
            ->toContain('General Info')
            ->toContain('rounded-none')
            ->toContain('border-ringside-line')
            ->toContain('bg-ringside-surface-header')
            ->not->toContain('var(--radius)')
            ->not->toContain('text-gray-');
    });
});
