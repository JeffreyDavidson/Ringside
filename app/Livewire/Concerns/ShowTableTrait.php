<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait ShowTableTrait
{
    public function mountShowTableTrait(): void
    {
        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }

    /**
     * Authorize viewing the record whose detail page hosts this table, so a table cannot
     * be mounted for a record the user may not see.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function authorizeContextRecord(string $modelClass, ?int $id, string $resource): void
    {
        Gate::authorize('view', $modelClass::query()->findOrFail($this->requireContextId($id, $resource)));
    }

    /**
     * Relationship history tables follow the Previous{Entity} naming convention and
     * only list ended records, so their heading says so ("Previous tag teams").
     */
    protected function tableHeading(): string
    {
        $resource = str_starts_with(class_basename($this), 'Previous')
            ? "previous {$this->resourceName}"
            : $this->resourceName;

        return Str::ucfirst($resource);
    }
}
