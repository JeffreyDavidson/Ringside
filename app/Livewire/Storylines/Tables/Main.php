<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Tables;

use App\Builders\Storylines\StorylineBuilder;
use App\Enums\Storylines\StorylineStatus;
use App\Models\Storylines\Storyline;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $participant = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Storyline::class);
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $this->search));
        $query = Storyline::query()->withSummaries()->with(['nextStep.event.promotion', 'lastDevelopment', 'participants']);
        $status = StorylineStatus::tryFrom($this->status);
        $query->when($needle !== '', fn (StorylineBuilder $builder) => $builder->whereLike('title', "%{$needle}%"));
        $query->when($status !== null, fn (StorylineBuilder $builder) => $builder->where('status', $status));
        $query->when($status === null, fn (StorylineBuilder $builder): StorylineBuilder => $builder->visible());
        $participant = mb_trim(str_replace(['%', '_', '\\'], '', $this->participant));
        $query->when($participant !== '', fn (StorylineBuilder $builder) => $builder->whereHas('participants', fn (Builder $people) => $people->whereLike('label', "%{$participant}%")));

        return view('livewire.storylines.tables.main', ['stories' => $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)]);
    }
}
