<?php

namespace App\Repositories;

use App\Models\Contracts\Deactivatable;
use App\Models\Title;
use Carbon\Carbon;

class TitleRepository implements DeactivationRepositoryInterface
{
    /**
     * Create a new title with the given data.
     *
     * @param  array $data
     * @return \App\Models\Title
     */
    public function create(array $data)
    {
        return Title::create($data);
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
     * Deactivate a given title with a given date.
     *
     * @param  \App\Models\Title $title
     * @param  \Carbon\Carbon|null $startedAt
     * @return \App\Models\Title $title
     */
    public function deactivate(Deactivatable $title, Carbon $startedAt = null)
    {
        return $title->currentActivation()->update(['ended_at' => $startedAt]);
    }
}
