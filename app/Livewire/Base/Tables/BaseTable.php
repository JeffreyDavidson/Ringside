<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;
use App\Livewire\Table\DataTableComponent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class BaseTable extends DataTableComponent
{
    use BaseTableTrait;
    use HasActionColumn;

    /** @var array<string, bool> */
    protected array $actionLinksToDisplay = ['view' => true, 'edit' => true, 'delete' => true];

    /**
     * Delete a model with proper authorization checking.
     *
     * @param  Model  $model  The model to delete
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
