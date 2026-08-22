<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\TitleChampionship;
use Livewire\Attributes\Locked;
use LogicException;

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
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        $wrestler = Wrestler::query()->findOrFail($this->wrestlerId);

        return TitleChampionship::query()
            ->forChampion($wrestler)
            ->forPreviousHistory();
    }
}
