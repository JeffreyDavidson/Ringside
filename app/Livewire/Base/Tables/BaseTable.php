<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasActionColumn;
use App\Livewire\Table\DataTableComponent;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends DataTableComponent<TModel>
 */
abstract class BaseTable extends DataTableComponent
{
    use BaseTableTrait;
    use HasActionColumn;

    /** @var array<string, bool> */
    protected array $actionLinksToDisplay = ['view' => true, 'edit' => true, 'delete' => true];
}
