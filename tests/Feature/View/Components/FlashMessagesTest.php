<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders a successful flash message accessibly', function (): void {
    session()->flash('status', 'The action succeeded.');

    $html = Blade::render('<x-flash-messages />');

    expect($html)
        ->toContain('The action succeeded.')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain('bg-success-light')
        ->toContain('x-on:flash-message.window');
});

test('it renders an error flash message accessibly', function (): void {
    session()->flash('error', 'The action failed.');

    $html = Blade::render('<x-flash-messages />');

    expect($html)
        ->toContain('The action failed.')
        ->toContain('role="alert"')
        ->toContain('aria-live="assertive"')
        ->toContain('bg-danger-light');
});
