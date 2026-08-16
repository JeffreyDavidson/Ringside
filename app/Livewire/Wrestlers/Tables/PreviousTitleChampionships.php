<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class PreviousTitleChampionships extends BasePreviousTitleChampionshipsTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    public string $databaseTableName = 'titles_championships';

    protected string $resourceName = 'title championships';

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        $wrestler = Wrestler::query()->findOrFail($this->wrestlerId);

        return TitleChampionship::query()
            ->forChampion($wrestler)
            ->previous()
            ->withPreviousChampionshipId()
            ->with(['title', 'previousChampionship.champion']);
    }
}
