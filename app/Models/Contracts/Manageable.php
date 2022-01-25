<?php

namespace App\Models\Contracts;

interface Manageable
{
    /**
     * Get all of the managers of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managers();

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentManagers();

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousManagers();
}
