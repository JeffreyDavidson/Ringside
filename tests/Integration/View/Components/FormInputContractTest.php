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

it('preserves existing descriptions when associating a validation error', function (): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-form.input wire:model="form.name" label="Name" aria-describedby="name-help" />');

    expect($html)->toContain('aria-describedby="name-help form.name-error"');
});

it('does not repeat a validation error in existing descriptions', function (): void {
    $errors = new ViewErrorBag()->put('default', new MessageBag(['form.name' => 'A name is required.']));
    view()->share('errors', $errors);

    $html = Blade::render('<x-form.input wire:model="form.name" label="Name" aria-describedby="name-help form.name-error" />');

    expect($html)->toContain('aria-describedby="name-help form.name-error"')
        ->not->toContain('aria-describedby="name-help form.name-error form.name-error"');
});

it('rejects non-string field names', function (string $component): void {
    expect(fn (): string => Blade::render('<x-'.$component.' :name="[1, 2]" />'))
        ->toThrow(ViewException::class, 'Form field names must be strings.');
})->with(['form.input', 'form.inputs.textarea', 'form.inputs.select']);
