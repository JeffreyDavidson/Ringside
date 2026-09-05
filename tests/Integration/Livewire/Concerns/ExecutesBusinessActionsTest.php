<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExecutesBusinessActions;

describe('business action execution', function (): void {
    it('flashes the provided message after a successful action', function (): void {
        // Arrange
        $component = new class
        {
            use ExecutesBusinessActions;

            /** @var list<array{event: string, parameters: array<array-key, mixed>}> */
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
        $expectedEvents = [
            [
                'event' => 'flash-message',
                'parameters' => [
                    'type' => 'status',
                    'message' => 'The action succeeded.',
                ],
            ],
        ];

        // Act
        $succeeded = $component->execute();

        // Assert
        expect($succeeded)->toBeTrue()
            ->and(session('status'))->toBe('The action succeeded.')
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });

    it('flashes and dispatches action failures', function (): void {
        // Arrange
        $component = new class
        {
            use ExecutesBusinessActions;

            /** @var list<array{event: string, parameters: array<array-key, mixed>}> */
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
        $expectedEvents = [
            [
                'event' => 'flash-message',
                'parameters' => [
                    'type' => 'error',
                    'message' => 'The action failed.',
                ],
            ],
        ];

        // Act
        $succeeded = $component->execute();

        // Assert
        expect($succeeded)->toBeFalse()
            ->and(session('error'))->toBe('The action failed.')
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });
});
