<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;

final class PreviousTitleChampionshipsTable extends BasePreviousTitleChampionshipsTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    protected string $databaseTableName = 'tittle_championships';

    protected string $resourceName = 'title championships';

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        // dd(TitleChampionship::query()
        //     ->whereHasMorph(
        //         'previousChampion',
        //         [Wrestler::class],
        //         function (Builder $query) {
        //             $query->whereIn('id', [$this->wrestlerId]);
        //         }
        //     )->get());

        return TitleChampionship::query()
            ->whereHasMorph(
                'previousChampion',
                [Wrestler::class],
                function (Builder $query): void {
                    $query->whereIn('id', [$this->wrestlerId]);
                }
            );
    }
}
