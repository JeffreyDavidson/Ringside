<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;

/**
 * Preserve the concrete Livewire component type for static analysis.
 *
 * @template TComponent of Component
 *
 * @param  class-string<TComponent>  $component
 * @param  array<array-key, mixed>  $params
 * @return Testable<TComponent>
 */
function testLivewire(string $component, array $params = []): Testable
{
    return Pest\Livewire\livewire($component, $params);
}
