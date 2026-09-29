<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

beforeEach(fn () => view()->share('errors', new ViewErrorBag));

it('escapes a bound label exactly once', function (string $component): void {
    $html = Blade::render(
        "<x-{$component} name=\"field\" route=\"/target\" :label=\"\$label\" />",
        ['label' => "O'Neil & Sons"],
    );

    expect($html)
        ->toContain('O&#039;Neil &amp; Sons')
        ->not->toContain('&amp;#039;')
        ->not->toContain('&amp;amp;');
})->with([
    'route-link',
    'form.input',
    'form.inputs.text',
    'form.inputs.date',
    'form.inputs.select',
    'form.inputs.textarea',
]);

it('does not let a bound label inject markup', function (string $component): void {
    $html = Blade::render(
        "<x-{$component} name=\"field\" route=\"/target\" :label=\"\$label\" />",
        ['label' => '<script>alert("x")</script>'],
    );

    expect($html)
        ->toContain('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;')
        ->not->toContain('<script>');
})->with([
    'route-link',
    'form.input',
    'form.inputs.text',
    'form.inputs.date',
    'form.inputs.select',
    'form.inputs.textarea',
]);
