<?php

namespace App\Models\Concerns;

trait HasManagers
{
    public function managers()
    {
        return $this->belongsToMany(Manager::class);
    }

    public function currentManagers()
    {
    }

    public function previousManagers()
    {
    }
}
