<?php

declare(strict_types=1);

namespace App\Livewire\Titles;

use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TitleChampionshipsList extends Component
{
    /**
     * Undocumented variable.
     */
    public Title $title;

    /**
     * List of filters that are allowed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * Undocumented function.
     */
    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->title
            ->championships()
            ->latest('won_at')
            ->latest('id');

        $titleChampionships = $query->paginate();

        return view('livewire.titles.title-championships-list', [
            'titleChampionships' => $titleChampionships,
        ]);
    }
}
