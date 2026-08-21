<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExecutesBusinessActions;

test('it flashes the provided message after a successful business action', function (): void {
    $component = new class
    {
        use ExecutesBusinessActions;

        /** @var list<array{event: string, parameters: array<string, mixed>}> */
        public array $dispatchedEvents = [];

        public function execute(): bool
        {
            return $this->executeBusinessAction(
                static function (): void {},
                'The action succeeded.',
            );
        }

        public function dispatch(string $event, mixed ...$parameters): void
        {
            $this->dispatchedEvents[] = [
                'event' => $event,
                'parameters' => $parameters,
            ];
        }
    };

    expect($component->execute())->toBeTrue()
        ->and(session('status'))->toBe('The action succeeded.')
        ->and($component->dispatchedEvents)->toBe([
            [
                'event' => 'flash-message',
                'parameters' => [
                    'type' => 'status',
                    'message' => 'The action succeeded.',
                ],
            ],
        ]);
});

test('it flashes and dispatches business action failures', function (): void {
    $component = new class
    {
        use ExecutesBusinessActions;

        /** @var list<array{event: string, parameters: array<string, mixed>}> */
        public array $dispatchedEvents = [];

        public function execute(): bool
        {
            return $this->executeBusinessAction(static function (): void {
                throw new class('The action failed.') extends BaseBusinessException {};
            });
        }

        public function dispatch(string $event, mixed ...$parameters): void
        {
            $this->dispatchedEvents[] = [
                'event' => $event,
                'parameters' => $parameters,
            ];
        }
    };

    expect($component->execute())->toBeFalse()
        ->and(session('error'))->toBe('The action failed.')
        ->and($component->dispatchedEvents)->toBe([
            [
                'event' => 'flash-message',
                'parameters' => [
                    'type' => 'error',
                    'message' => 'The action failed.',
                ],
            ],
        ]);
});
