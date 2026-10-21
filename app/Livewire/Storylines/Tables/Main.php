<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Tables;

use App\Builders\Storylines\StorylineBuilder;
use App\Enums\Storylines\StorylineStatus;
use App\Models\Storylines\Storyline;
use App\Support\Search\LikeTerm;
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
        $like = LikeTerm::from($this->search);
        $query = Storyline::query()->withSummaries()->with(['nextStep.event.promotion', 'lastDevelopment', 'participants']);
        $status = StorylineStatus::tryFrom($this->status);
        $query->when(! $like->isEmpty(), fn (StorylineBuilder $builder) => $builder->whereLike('title', $like->contains()));
        $query->when($status !== null, fn (StorylineBuilder $builder) => $builder->where('status', $status));
        $query->when($status === null, fn (StorylineBuilder $builder): StorylineBuilder => $builder->visible());
        $participant = LikeTerm::from($this->participant);
        $query->when(! $participant->isEmpty(), fn (StorylineBuilder $builder) => $builder->whereHas('participants', fn (Builder $people) => $people->whereLike('label', $participant->contains())));

        return view('livewire.storylines.tables.main', ['stories' => $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)]);
    }
}
