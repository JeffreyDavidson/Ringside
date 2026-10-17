<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

describe('button variants', function (): void {
    it('renders every variant as a square Ringside button', function (string $variant, string $brandClass): void {
        $html = Blade::render('<x-button variant="'.$variant.'">Save</x-button>');

        expect($html)
            ->toContain($brandClass)
            ->toContain('rounded-none')
            ->toContain('focus-visible:outline-foreground')
            ->not->toContain('outline-white')
            ->not->toContain('rounded-md')
            ->not->toContain('btn-');
    })->with([
        'primary' => ['primary', 'bg-primary'],
        'ringside' => ['ringside', 'bg-primary'],
        'secondary' => ['secondary', 'border-outline'],
        'light' => ['light', 'border-outline'],
        'success' => ['success', 'border-outline'],
        'warning' => ['warning', 'border-outline'],
        'info' => ['info', 'border-outline'],
        'destructive' => ['destructive', 'text-signal-soft'],
        'danger' => ['danger', 'text-signal-soft'],
    ]);

    it('gives the primary button a lighter hover red and a deeper pressed red', function (): void {
        $html = Blade::render('<x-button>Save</x-button>');

        expect($html)
            ->toContain('hover:bg-primary-hover')
            ->toContain('hover:border-primary-hover')
            ->toContain('active:bg-primary-pressed')
            ->toContain('active:border-primary-pressed');
    });

    it('falls back to the primary variant for unknown variants', function (): void {
        $html = Blade::render('<x-button variant="unknown">Save</x-button>');

        expect($html)->toContain('bg-primary');
    });

    it('renders the variant wrappers with the shared button system', function (string $component, string $brandClass): void {
        $html = Blade::render('<x-buttons.'.$component.'>Go</x-buttons.'.$component.'>');

        expect($html)->toContain($brandClass);
    })->with([
        'primary' => ['primary', 'bg-primary'],
        'light' => ['light', 'border-outline'],
        'success' => ['success', 'border-outline'],
        'warning' => ['warning', 'border-outline'],
        'danger' => ['danger', 'text-signal-soft'],
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
