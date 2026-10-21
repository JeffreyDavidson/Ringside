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
use App\Livewire\Table\Filters\SelectFilter;
use BackedEnum;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
     * The Status select filter of an index table: the given options with Deleted appended for people who may restore
     * the table's records, and one query per status value.
     *
     * Deleted lists only soft-deleted rows. Anyone who may not restore gets no deleted rows even when they force the
     * value into the filter: it then falls through to the status handling, where it matches no status and filters
     * nothing, as does any other unknown value.
     *
     * @template TStatus of BackedEnum
     *
     * @param  class-string<TStatus>  $statusEnum
     * @param  array<string, string>  $options
     * @param  Closure(Builder<*>, TStatus): mixed  $whereStatus
     */
    protected function statusFilter(string $statusEnum, array $options, Closure $whereStatus): SelectFilter
    {
        return SelectFilter::make(__('core.status'), 'status')
            ->options($this->canRestoreRecords() ? DeletedFilter::appendTo($options) : $options)
            ->filter(function (Builder $builder, string $value) use ($statusEnum, $whereStatus): void {
                if ($this->canRestoreRecords() && DeletedFilter::tryFrom($value) !== null) {
                    // Every table with a Deleted option lists a soft-deleting model, which static analysis cannot see through the generic builder.
                    $builder->onlyTrashed(); // @phpstan-ignore method.notFound

                    return;
                }

                $status = $statusEnum::tryFrom($value);

                if ($status !== null) {
                    $whereStatus($builder, $status);
                }
            });
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
