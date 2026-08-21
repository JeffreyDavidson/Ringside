<?php

declare(strict_types=1);

use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;

test('it rejects lifecycle actions unsupported by the roster entity', function (): void {
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

    expect(fn () => $component->execute(RosterLifecycleAction::Injure, RosterEntityType::TagTeam, new TagTeam()))
        ->toThrow(InvalidArgumentException::class, 'injure is not a tag-team lifecycle action.');
});
