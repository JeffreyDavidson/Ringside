<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

describe('button variants', function (): void {
    it('renders every variant as a square Ringside button', function (string $variant, string $brandClass): void {
        $html = Blade::render('<x-button variant="'.$variant.'">Save</x-button>');

        expect($html)
            ->toContain($brandClass)
            ->toContain('rounded-none')
            ->toContain('focus-visible:outline-ringside-white')
            ->not->toContain('rounded-md')
            ->not->toContain('btn-');
    })->with([
        'primary' => ['primary', 'bg-ringside-red'],
        'ringside' => ['ringside', 'bg-ringside-red'],
        'secondary' => ['secondary', 'border-ringside-outline'],
        'light' => ['light', 'border-ringside-outline'],
        'success' => ['success', 'border-ringside-outline'],
        'warning' => ['warning', 'border-ringside-outline'],
        'info' => ['info', 'border-ringside-outline'],
        'destructive' => ['destructive', 'text-ringside-signal-soft'],
        'danger' => ['danger', 'text-ringside-signal-soft'],
    ]);

    it('falls back to the primary variant for unknown variants', function (): void {
        $html = Blade::render('<x-button variant="unknown">Save</x-button>');

        expect($html)->toContain('bg-ringside-red');
    });

    it('renders the variant wrappers with the shared button system', function (string $component, string $brandClass): void {
        $html = Blade::render('<x-buttons.'.$component.'>Go</x-buttons.'.$component.'>');

        expect($html)->toContain($brandClass);
    })->with([
        'primary' => ['primary', 'bg-ringside-red'],
        'light' => ['light', 'border-ringside-outline'],
        'success' => ['success', 'border-ringside-outline'],
        'warning' => ['warning', 'border-ringside-outline'],
        'danger' => ['danger', 'text-ringside-signal-soft'],
    ]);
});

describe('button sizes', function (): void {
    it('gives the default size a 44px minimum hit target', function (): void {
        $html = Blade::render('<x-button>Save</x-button>');

        expect($html)->toContain('min-h-11');
    });

    it('renders as a link when a tag is given', function (): void {
        $html = Blade::render('<x-button tag="a" href="/events">Events</x-button>');

        expect($html)
            ->toContain('<a')
            ->toContain('href="/events"')
            ->not->toContain('type="button"');
    });
});
