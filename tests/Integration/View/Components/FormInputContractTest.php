<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

it('derives field names from Livewire bindings and renders validation errors', function (string $component): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-'.$component.' wire:model.live="form.name" label="Name" />');

    expect($html)
        ->toContain('name="form.name"')
        ->toContain('id="form.name"')
        ->toContain('for="form.name"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="form.name-error"')
        ->toContain('id="form.name-error"')
        ->toContain('A name is required.');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('uses custom IDs for labels and validation descriptions', function (string $component): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-'.$component.' wire:model="form.name" id="custom-name" label="Name" />');

    expect($html)
        ->toContain('id="custom-name"')
        ->toContain('for="custom-name"')
        ->toContain('aria-describedby="custom-name-error"')
        ->toContain('id="custom-name-error"');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('preserves existing descriptions when associating a validation error', function (string $component): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-'.$component.' wire:model="form.name" label="Name" aria-describedby="name-help" />');

    expect($html)->toContain('aria-describedby="name-help form.name-error"');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('does not repeat a validation error in existing descriptions', function (string $component): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-'.$component.' wire:model="form.name" label="Name" aria-describedby="name-help form.name-error" />');

    expect($html)->toContain('aria-describedby="name-help form.name-error"')
        ->not->toContain('aria-describedby="name-help form.name-error form.name-error"');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('rejects non-string field names', function (string $component): void {
    view()->share('errors', new ViewErrorBag);

    expect(fn (): string => Blade::render('<x-'.$component.' :name="[1, 2]" />'))
        ->toThrow(ViewException::class, 'Form field names must be strings.');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('rejects non-string field IDs', function (string $component) {
    view()->share('errors', new ViewErrorBag);

    $render = fn (): string => Blade::render('<x-'.$component.' name="name" :id="[1, 2]" />');

    expect($render)->toThrow(ViewException::class, 'Form field IDs must be strings.');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('marks invalid fields with the Ringside error styling', function (string $component): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-'.$component.' wire:model="form.name" label="Name" />');

    expect($html)
        ->toContain('aria-invalid:border-ringside-signal-soft')
        ->toContain('text-ringside-signal-soft')
        ->toContain('<svg')
        ->not->toContain('var(--input)')
        ->not->toContain('text-destructive');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);

it('renders standalone errors as a visible alert with an icon', function (): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.configuration' => 'This wrestler is not available for booking.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-form.error name="form.configuration" />');

    expect($html)
        ->toContain('role="alert"')
        ->toContain('text-ringside-signal-soft')
        ->toContain('<svg')
        ->toContain('This wrestler is not available for booking.');
});
