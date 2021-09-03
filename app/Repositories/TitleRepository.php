<?php

namespace App\Repositories;

use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Title;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\Contracts\DeactivationRepositoryInterface;

class TitleRepository implements ActivationRepositoryInterface, DeactivationRepositoryInterface
{
    /**
     * Create a new title with the given data.
     *
     * @param  array $data
     * @return \App\Models\Title
     */
    public function create(array $data)
    {
        return Title::create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Update the given title with the given data.
     *
     * @param  \App\Models\Title $title
     * @param  array $data
     * @return \App\Models\Title $title
     */
    public function update(Title $title, array $data)
    {
        return $title->update([
            'name' => $data['name'],
        ]);
    }

    /**
     * Delete a given title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function delete(Title $title)
    {
        $title->delete();
    }

    /**
     * Restore a given title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function restore(Title $title)
    {
        $title->restore();
    }

    /**
     * Activate a given title on a given date.
     *
     * @param  \App\Models\Contracts\Activatable $title
     * @param  string $activationDate
     * @return \App\Models\Title $title
     */
    public function activate(Activatable $title, string $activationDate)
    {
        $title->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $activationDate]);

        return $title;
    }

    /**
     * Deactivate a given title on a given date.
     *
     * @param  \App\Models\Contracts\Deactivatable $title
     * @param  string $deactivationDate
     * @return \App\Models\Title $title
     */
    public function deactivate(Deactivatable $title, string $deactivationDate)
    {
        $title = $title->currentActivation()->update(['ended_at' => $deactivationDate]);

        return $title;
    }

    /**
     * Retire a given title on a given date.
     *
     * @param  \App\Models\Title $title
     * @param  string $retirementDate
     * @return \App\Models\Title $title
     */
    public function retire(Title $title, string $retirementDate)
    {
        return $title->retirements()->create(['started_at' => $retirementDate]);
    }

    /**
     * Unretire a given title on a given date.
     *
     * @param  \App\Models\Title $title
     * @param  string $unretireDate
     * @return \App\Models\Title $title
     */
    public function unretire(Title $title, string $unretireDate)
    {
        return $title->currentRetirement()->update(['ended_at' => $unretireDate]);
    }
}
