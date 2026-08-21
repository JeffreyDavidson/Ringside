<?php

declare(strict_types=1);

use App\Livewire\Concerns\ExecutesBusinessActions;

test('it flashes the provided message after a successful business action', function (): void {
    $component = new class
    {
        use ExecutesBusinessActions;

        public function execute(): bool
        {
            return $this->executeBusinessAction(
                static function (): void {},
                'The action succeeded.',
            );
        }
    };

    expect($component->execute())->toBeTrue()
        ->and(session('status'))->toBe('The action succeeded.');
});
