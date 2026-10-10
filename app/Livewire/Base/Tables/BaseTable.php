<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Enums\Roster\RosterEntityType;
use App\Enums\Shared\DeletedFilter;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Support\RosterErrorMessageResolver;
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
     * Status filter options, with Deleted appended only for people who may restore the table's records.
     *
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    protected function statusOptionsWithDeleted(array $options): array
    {
        return $this->canRestoreRecords() ? DeletedFilter::appendTo($options) : $options;
    }

    /**
     * Whether a status filter value asks for the Deleted list. Anyone who may not restore gets no deleted rows, even
     * when they force the value into the filter, so the closure falls through to the normal status handling.
     */
    protected function isDeletedFilterValue(string $value): bool
    {
        return $this->canRestoreRecords() && DeletedFilter::tryFrom($value) !== null;
    }

    private function canRestoreRecords(): bool
    {
        return Gate::allows('restore', $this->modelClass);
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

    /**
     * Restore a soft-deleted listed record through its Action, then drop the remembered status counts it may change.
     *
     * Roster entities pass their type so a refusal shows the translated roster message instead of the exception text.
     *
     * @template TRecord of Model
     *
     * @param  TRecord  $record
     * @param  Closure(TRecord): mixed  $restoreAction
     */
    protected function restoreRecord(Model $record, Closure $restoreAction, string $successMessage, ?RosterEntityType $rosterEntityType = null): void
    {
        Gate::authorize('restore', $record);

        $this->executeBusinessAction(
            function () use ($restoreAction, $record): void {
                $restoreAction($record);
            },
            $successMessage,
            $rosterEntityType instanceof RosterEntityType
                ? fn (BaseBusinessException $exception): string => RosterErrorMessageResolver::message($exception, $rosterEntityType)
                : null,
        );

        $this->forgetMetadata();
    }
}
