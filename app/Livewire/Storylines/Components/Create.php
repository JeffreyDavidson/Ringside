<?php

declare(strict_types=1);

namespace App\Livewire\Storylines\Components;

use App\Actions\Storylines\CreateAction;
use App\Data\Storylines\StorylineData;
use App\Livewire\Concerns\ResolvesActor;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Create extends Component
{
    use ResolvesActor;

    public string $title = '';

    public string $premise = '';

    public string $payoff = '';

    public ?int $promotionId = null;

    #[Locked]
    public string $submissionId;

    protected PromotionContextService $promotionContext;

    public function boot(PromotionContextService $promotionContext): void
    {
        $this->promotionContext = $promotionContext;
    }

    public function mount(): void
    {
        Gate::authorize('create', Storyline::class);
        $this->submissionId = (string) Str::uuid();
        $this->promotionId = $this->promotionContext->current()?->id;
    }

    public function save(CreateAction $create): void
    {
        Gate::authorize('create', Storyline::class);
        $this->validate(['submissionId' => ['required', 'uuid'], 'promotionId' => ['required', 'integer'], 'title' => ['required', 'string', 'max:160'], 'premise' => ['required', 'string', 'max:4000'], 'payoff' => ['nullable', 'string', 'max:4000']]);
        abort_if($this->promotionContext->isEnforced() && $this->promotionContext->current()?->id !== $this->promotionId, 403);
        $story = $create->handle(Promotion::query()->findOrFail($this->promotionId), new StorylineData($this->title, $this->premise, $this->payoff ?: null, $this->submissionId), $this->actor());
        $this->redirectRoute('storylines.show', ['storyline' => $story]);
    }

    public function render(): View
    {
        Gate::authorize('create', Storyline::class);

        return view('livewire.storylines.components.create', ['promotions' => $this->promotionContext->isEnforced() ? collect([$this->promotionContext->required()]) : Promotion::query()->orderBy('name')->orderBy('id')->get()]);
    }
}
