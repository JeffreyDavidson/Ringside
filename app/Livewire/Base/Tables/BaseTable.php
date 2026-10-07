<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\DataTableComponent;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * @template TModel of Model
 *
 * @extends DataTableComponent<TModel>
 */
abstract class BaseTable extends DataTableComponent
{
    use BaseTableTrait;
    use ExecutesBusinessActions;

    /**
     * Model listed by the table; viewing the index requires the `viewAny` ability on it.
     *
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * Dedicated Blade view for tables that do not use the generic data table view.
     *
     * @var view-string|null
     */
    protected ?string $indexView = null;

    protected function configure(): void
    {
        Gate::authorize('viewAny', $this->modelClass);
    }

    public function render(): View
    {
        if ($this->indexView === null) {
            return parent::render();
        }

        return view($this->indexView, [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
            'beforeWrapperView' => $this->beforeWrapperView,
        ]);
    }

    /**
     * Delete a listed record through its Action, then drop the remembered status counts it may change.
     *
     * @template TRecord of Model
     *
     * @param  TRecord  $record
     * @param  Closure(TRecord): mixed  $deleteAction
     */
    protected function deleteRecord(Model $record, Closure $deleteAction, string $successMessage): void
    {
        Gate::authorize('delete', $record);

        $this->executeBusinessAction(function () use ($deleteAction, $record): void {
            $deleteAction($record);
        }, $successMessage);

        $this->forgetMetadata();
    }
}
