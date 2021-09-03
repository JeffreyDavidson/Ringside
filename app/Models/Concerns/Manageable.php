<?php

namespace App\Models\Concerns;

trait Manageable
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
