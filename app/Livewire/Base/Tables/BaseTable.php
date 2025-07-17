<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Base class for all table components.
 *
 * This abstract class provides the foundation for all table components in the
 * application. It handles common table functionality including actions, deletion,
 * and integrates with the Laravel Livewire Tables package.
 *
 * The class supports both simple tables and tables with actions through the
 * HasActionColumn trait, which can be optionally enabled by setting the
 * $showActionColumn property to true in child classes.
 *
 * Key Features:
 * - Consistent table styling and behavior
 * - Optional action column support
 * - Standardized deletion handling with authorization
 * - Integration with Laravel Gates for permissions
 * - Event dispatching for real-time updates
 *
 * @author Your Name
 * @since 1.0.0
 */
abstract class BaseTable extends DataTableComponent
{
    use BaseTableTrait;
    use HasActionColumn;

    /** @var array<string, bool> */
    protected array $actionLinksToDisplay = ['view' => true, 'edit' => true, 'delete' => true];

    /**
     * Delete a model with proper authorization checking.
     *
     * @param Model $model The model to delete
     */
    protected function deleteModel(Model $model): void
    {
        $canDelete = Gate::inspect('delete', $model);

        if ($canDelete->allowed()) {
            $model->delete();
            session()->flash('status', 'Model successfully updated.');
        } else {
            session()->flash('status', 'You cannot delete this Model.');
        }
    }
}
