<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use App\Livewire\Wrestler\Index\Traits\Searchable;
use App\Livewire\Wrestler\Index\Traits\Sortable;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use Searchable;
    use Sortable;
    use WithPagination;

    public array $selectedWrestlerIds = [];

    public array $wrestlerIdsOnPage = [];

    public function deleteSelected(): void
    {
        $wrestlers = Wrestler::query()->whereIn('id', $this->selectedWrestlerIds)->get();

        foreach ($wrestlers as $wrestler) {
            $this->delete($wrestler);
        }
    }

    public function delete(Wrestler $wrestler): void
    {
        $this->authorize('delete', $wrestler);

        $wrestler->delete();
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Wrestler::query()
            ->orderBy('name');

        $query = $this->applySearch($query);

        $query = $this->applySorting($query);

        $wrestlers = $query->paginate(10);

        $this->wrestlerIdsOnPage = $wrestlers->map(fn ($wrestler) => (string) $wrestler->id)->toArray();

        return view('livewire.wrestler.index.table', [
            'wrestlers' => $wrestlers,
        ]);
    }
}
