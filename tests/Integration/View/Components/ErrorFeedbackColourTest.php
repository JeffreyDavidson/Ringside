<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

describe('error feedback colour', function (): void {
    it('shows error feedback in the error colour, not the destructive coral', function (string $errorKey, string $template): void {
        // Arrange
        view()->share('errors', new ViewErrorBag()->put('default', new MessageBag([$errorKey => 'Something needs fixing.'])));

        // Act
        $html = Blade::render($template);

        // Assert
        expect($html)
            ->toContain('Something needs fixing.')
            ->toContain('error')
            ->not->toContain('signal-soft');
    })->with([
        'stable error panel' => ['stable', '<x-stables.modal-error />'],
        'sign-in field' => ['email', '<x-auth.field name="email" label="Email" />'],
        'roster search field' => ['form.wrestlers', '<x-form.inputs.roster-combobox wire:model="form.wrestlers" label="Wrestlers" :kind="\\App\\Enums\\Roster\\RosterMemberKind::Wrestlers" />'],
    ]);

    it('pairs the sign-in field error with an icon so colour is never the only signal', function (): void {
        // Arrange
        view()->share('errors', new ViewErrorBag()->put('default', new MessageBag(['email' => 'Something needs fixing.'])));

        // Act
        $html = Blade::render('<x-auth.field name="email" label="Email" />');

        // Assert
        expect($html)
            ->toContain('id="email-error"')
            ->toContain('role="alert"')
            ->toContain('<svg');
    });

    it('tints error panels with the error surface', function (): void {
        // Arrange
        view()->share('errors', new ViewErrorBag()->put('default', new MessageBag(['stable' => 'Something needs fixing.'])));

        // Act
        $html = Blade::render('<x-stables.modal-error />');

        // Assert
        expect($html)
            ->toContain('bg-error-surface')
            ->toContain('border-error');
    });

    it('marks an error flash message with the error colour', function (): void {
        // Act
        $html = Blade::render('<x-flash-messages />');

        // Assert
        expect($html)
            ->toContain("'border-error': notification?.type === 'error'")
            ->not->toContain('signal-soft');
    });

    it('keeps destructive actions in coral so they are not mistaken for errors', function (): void {
        // Act
        $html = Blade::render('<x-button variant="destructive">Delete</x-button>');

        // Assert
        expect($html)
            ->toContain('text-signal-soft')
            ->not->toContain('error');
    });
});
