<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('it renders a successful flash message for the polite live region', function (): void {
    session()->flash('status', 'The action succeeded.');

    $html = Blade::render('<x-flash-messages />');

    expect($html)
        ->toContain('data-notification-type="status"')
        ->toContain('data-notification-message="The action succeeded."')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain('x-on:flash-message.window');
});

test('it renders an error flash message for the assertive live region', function (): void {
    session()->flash('error', 'The action failed.');

    $html = Blade::render('<x-flash-messages />');

    expect($html)
        ->toContain('data-notification-type="error"')
        ->toContain('data-notification-message="The action failed."')
        ->toContain('role="alert"')
        ->toContain('aria-live="assertive"');
});

test('it keeps both live regions rendered when there is no flash message', function (): void {
    $html = Blade::render('<x-flash-messages />');

    expect($html)
        ->toContain('data-test="flash-status-region"')
        ->toContain('data-test="flash-alert-region"')
        ->toContain('data-notification-message=""');
});
