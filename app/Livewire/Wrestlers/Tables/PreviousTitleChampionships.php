<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

class PreviousTitleChampionships extends BasePreviousTitleChampionshipsTable
{
    /**
     * Wrestler to use for component.
     */
    #[Locked]
    public ?int $wrestlerId;

    public string $databaseTableName = 'titles_championships';

    protected string $resourceName = 'title championships';

    /** @return TitleChampionshipBuilder<TitleChampionship> */
    public function builder(): TitleChampionshipBuilder
    {
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        return TitleChampionship::query()
            ->forWrestlerId($wrestlerId)
            ->forPreviousHistory();
    }

    protected function configure(): void
    {
        parent::configure();

        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        Gate::authorize('view', Wrestler::query()->findOrFail($wrestlerId));
    }
}
