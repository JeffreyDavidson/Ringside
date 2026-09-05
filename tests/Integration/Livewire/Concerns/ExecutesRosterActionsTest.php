<?php

declare(strict_types=1);

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;

describe('roster action execution', function (): void {
    it('flashes successful actions under the standard status key', function (): void {
        // Arrange
        $component = new class
        {
            use ExecutesRosterActions;

            /** @var list<array{event: string, parameters: array<array-key, mixed>}> */
            public array $dispatchedEvents = [];

            public function execute(): bool
            {
                return $this->executeRosterAction(
                    'employed',
                    RosterEntityType::Wrestler,
                    static function (): void {},
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
                'event' => 'wrestler-updated',
                'parameters' => [],
            ],
            [
                'event' => 'flash-message',
                'parameters' => [
                    'type' => 'status',
                    'message' => 'Wrestler has been hired.',
                ],
            ],
        ];

        // Act
        $succeeded = $component->execute();

        // Assert
        expect($succeeded)->toBeTrue()
            ->and(session('status'))->toBe('Wrestler has been hired.')
            ->and(session('success'))->toBeNull()
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });

    it('rejects lifecycle actions unsupported by the roster entity', function (): void {
        // Arrange
        $component = new class
        {
            use ExecutesRosterActions;

            public function execute(RosterLifecycleAction $action, RosterEntityType $entityType, Model $model): void
            {
                $this->executeAuthorizedRosterAction($action, $entityType, $model, static function (): void {
                    throw new RuntimeException('The unsupported action should not execute.');
                });
            }
        };

        $execute = fn () => $component->execute(
            RosterLifecycleAction::Injure,
            RosterEntityType::TagTeam,
            new TagTeam(),
        );

        // Act / Assert
        expect($execute)
            ->toThrow(InvalidArgumentException::class, 'injure is not a tag-team lifecycle action.');
    });

    it('translates failures without dispatching an update', function (): void {
        // Arrange
        $manager = Manager::factory()->employed()->create();
        $component = new class($manager)
        {
            use ExecutesRosterActions;

            /** @var list<array{event: string, parameters: array<array-key, mixed>}> */
            public array $dispatchedEvents = [];

            public function __construct(private readonly Manager $manager) {}

            public function execute(): bool
            {
                return $this->executeRosterAction(
                    'employed',
                    RosterEntityType::Manager,
                    fn (): never => throw CannotBeEmployedException::employed($this->manager),
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
                    'type' => 'error',
                    'message' => 'This manager is already hired.',
                ],
            ],
        ];

        // Act
        $succeeded = $component->execute();

        // Assert
        expect($succeeded)->toBeFalse()
            ->and(session('error'))->toBe('This manager is already hired.')
            ->and($component->dispatchedEvents)->toBe($expectedEvents);
    });
});
