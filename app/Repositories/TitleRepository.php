<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TitleData;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TitleRepository
{
    public function create(TitleData $titleData): Model
    {
        return Title::create(['name' => $titleData->name]);
    }

    /**
     * Update the given title with the given data.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Data\TitleData  $titleData
     * @return \App\Models\Title
     */
    public function update(Title $title, TitleData $titleData)
    {
        $title->update([
            'name' => $titleData->name,
        ]);

        return $title;
    }

    /**
     * Delete a given title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function delete(Title $title)
    {
        $title->delete();
    }

    /**
     * Restore a given title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function restore(Title $title)
    {
        $title->restore();
    }

    /**
     * Activate a given title on a given date.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon  $activationDate
     * @return \App\Models\Title
     */
    public function activate(Title $title, Carbon $activationDate)
    {
        $title->activations()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $activationDate->toDateTimeString()]
        );
        $title->save();

        return $title;
    }

    /**
     * Deactivate a given title on a given date.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon  $deactivationDate
     * @return \App\Models\Title
     */
    public function deactivate(Title $title, Carbon $deactivationDate)
    {
        $title->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);
        $title->save();

        return $title;
    }

    /**
     * Retire a given title on a given date.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon  $retirementDate
     * @return \App\Models\Title
     */
    public function retire(Title $title, Carbon $retirementDate)
    {
        $title->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $title->save();

        return $title;
    }

    /**
     * Unretire a given title on a given date.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon  $unretireDate
     * @return \App\Models\Title
     */
    public function unretire(Title $title, Carbon $unretireDate)
    {
        $title->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $title->save();

        return $title;
    }
}
