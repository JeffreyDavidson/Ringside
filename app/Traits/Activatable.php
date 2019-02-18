<?php

namespace App\Traits;

trait Activatable
{
    /**
     * Check to see if the model is currently active.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Deactivate an active model.
     *
     * @return boolean
     */
    public function deactivate()
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Activate an inactive model.
     *
     * @return boolean
     */
    public function activate()
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Scope a query to only include active models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
