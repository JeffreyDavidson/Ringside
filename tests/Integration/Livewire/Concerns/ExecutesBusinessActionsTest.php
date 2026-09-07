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

            public int $actionCalls = 0;

            /** @var list<array{event: string, parameters: array<array-key, mixed>}> */
            public array $dispatchedEvents = [];

            public function execute(): bool
            {
                return $this->executeBusinessAction(
                    function (): void {
                        $this->actionCalls++;
                    },
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
            ->and($component->actionCalls)->toBe(1)
            ->and(session('status'))->toBe('The action succeeded.')
            ->and(session()->has('error'))->toBeFalse()
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });

    it('executes an action without feedback when no success message is supplied', function (): void {
        // Arrange
        $component = new class
        {
            use ExecutesBusinessActions;

            public int $actionCalls = 0;

            /** @var list<string> */
            public array $dispatchedEvents = [];

            public function execute(): bool
            {
                return $this->executeBusinessAction(function (): void {
                    $this->actionCalls++;
                });
            }

            public function dispatch(string $event, mixed ...$parameters): void
            {
                $this->dispatchedEvents[] = $event;
            }
        };

        // Act
        $succeeded = $component->execute();

        // Assert
        expect($succeeded)->toBeTrue()
            ->and($component->actionCalls)->toBe(1)
            ->and($component->dispatchedEvents)->toBeEmpty()
            ->and(session()->has('status'))->toBeFalse()
            ->and(session()->has('error'))->toBeFalse();
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
                }, 'The action succeeded.');
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
            ->and(session()->has('status'))->toBeFalse()
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });

    it('propagates unexpected failures without emitting feedback', function (Throwable $exception): void {
        // Arrange
        $component = new class
        {
            use ExecutesBusinessActions;

            /** @var list<string> */
            public array $dispatchedEvents = [];

            public function execute(Throwable $exception): bool
            {
                return $this->executeBusinessAction(fn (): never => throw $exception, 'The action succeeded.');
            }

            public function dispatch(string $event, mixed ...$parameters): void
            {
                $this->dispatchedEvents[] = $event;
            }
        };

        // Act / Assert
        expect(fn () => $component->execute($exception))->toThrow($exception);

        expect($component->dispatchedEvents)->toBeEmpty()
            ->and(session()->has('status'))->toBeFalse()
            ->and(session()->has('error'))->toBeFalse();
    })->with([
        'programming failure' => [new LogicException('Unexpected action state.')],
        'runtime failure' => [new RuntimeException('Action dependency failed.')],
    ]);
});
