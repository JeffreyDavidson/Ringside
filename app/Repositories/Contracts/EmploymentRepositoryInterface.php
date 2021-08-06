<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface EmploymentRepositoryInterface
{
    /**
     * Employ a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $employmentDate
     * @return \Illuminate\Database\Eloquent\Model $model
     */
    public function employ(Model $model, string $employmentDate);
}
