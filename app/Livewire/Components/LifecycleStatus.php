<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Status row of a show page's General Info card. Re-resolves the model on every
 * render so the computed status reflects lifecycle actions taken on the page.
 */
class LifecycleStatus extends Component
{
    /** @var class-string<Wrestler|Manager|Referee|TagTeam|Title> */
    #[Locked]
    public string $modelClass;

    #[Locked]
    public int $modelId;

    #[Locked]
    public string $updatedEvent;

    public function mount(Wrestler|Manager|Referee|TagTeam|Title $model, string $updatedEvent): void
    {
        $this->modelClass = $model::class;
        $this->modelId = $model->id;
        $this->updatedEvent = $updatedEvent;
    }

    /** @return array<string, string> */
    protected function getListeners(): array
    {
        return [$this->updatedEvent => '$refresh'];
    }

    public function render(): View
    {
        return view('livewire.components.lifecycle-status', [
            'status' => $this->modelClass::query()->findOrFail($this->modelId)->status,
        ]);
    }
}
