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
     */
    public function update(Title $title, TitleData $titleData): Title
    {
        $title->update([
            'name' => $titleData->name,
        ]);

        return $title;
    }

    /**
     * Delete a given title.
     */
    public function delete(Title $title): void
    {
        $title->delete();
    }

    /**
     * Restore a given title.
     */
    public function restore(Title $title): void
    {
        $title->restore();
    }

    /**
     * Activate a given title on a given date.
     */
    public function activate(Title $title, Carbon $activationDate): Title
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
     */
    public function deactivate(Title $title, Carbon $deactivationDate): Title
    {
        $title->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);
        $title->save();

        return $title;
    }

    /**
     * Retire a given title on a given date.
     */
    public function retire(Title $title, Carbon $retirementDate): Title
    {
        $title->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $title->save();

        return $title;
    }

    /**
     * Unretire a given title on a given date.
     */
    public function unretire(Title $title, Carbon $unretireDate): Title
    {
        $title->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $title->save();

        return $title;
    }
}
