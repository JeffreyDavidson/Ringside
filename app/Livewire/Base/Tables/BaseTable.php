<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\BaseTableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

abstract class BaseTable extends DataTableComponent
{
    use BaseTableTrait;

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
