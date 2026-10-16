<?php

declare(strict_types=1);

namespace App\Livewire\Sponsors\Components;

use App\Actions\Sponsors\SaveSponsorAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Sponsors\Forms\SponsorForm;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\SponsorCommitmentsViewModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Directory extends Component
{
    use ExplainsKeptInput;
    use WithPagination;

    #[Locked]
    public int $promotionId;

    #[Locked]
    public ?int $sponsorId = null;

    #[Locked]
    public ?int $draftId = null;

    #[Locked]
    public int $draftVersion = 0;

    #[Locked]
    public string $submissionKey = '';

    public SponsorForm $form;

    public bool $formShown = false;

    public string $search = '';

    public string $notice = '';

    public function mount(int $promotionId, ?int $sponsorId = null): void
    {
        $this->promotionId = $promotionId;
        $this->sponsorId = $sponsorId;
        Gate::authorize('manageSponsors', $this->promotion());
    }

    public function newSponsor(): void
    {
        Gate::authorize('manageSponsors', $this->promotion());
        $this->form->reset();
        $this->draftId = null;
        $this->draftVersion = 0;
        $this->submissionKey = (string) Str::uuid();
        $this->formShown = true;
        $this->dispatch('sponsor-draft-opened');
        $this->notice = '';
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manageSponsors', $this->promotion());
        $sponsor = Sponsor::query()->where('promotion_id', $this->promotionId)->findOrFail($id);
        Gate::authorize('manageSponsors', $sponsor);
        $this->form->load($sponsor);
        $this->draftId = $sponsor->id;
        $this->draftVersion = $sponsor->version;
        $this->submissionKey = $sponsor->submission_key;
        $this->formShown = true;
        $this->dispatch('sponsor-draft-opened');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageSponsors', $promotion);
        $data = $this->form->toData();
        try {
            app(SaveSponsorAction::class)->handle($promotion, $this->draftId, $this->draftVersion, $this->submissionKey, $data);
            $this->formShown = false;
            $this->notice = __('sponsors.sponsor_saved');
        } catch (BaseBusinessException $exception) {
            $this->addError('save', $this->failureMessageKeepingInput($exception));
        }
    }

    public function cancelDraft(): void
    {
        Gate::authorize('manageSponsors', $this->promotion());
        $this->formShown = false;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $promotion = $this->promotion();
        Gate::authorize('manageSponsors', $promotion);
        $sponsors = Sponsor::query()->where('promotion_id', $promotion->id)->whereLike('name', '%'.mb_substr($this->search, 0, 80).'%')
            ->orderBy('name')->orderBy('id')->paginate(20, pageName: 'sponsorsPage');
        $selected = $this->sponsorId === null ? null : Sponsor::query()->where('promotion_id', $promotion->id)->findOrFail($this->sponsorId);
        $history = $selected === null ? null : $selected->commitments()
            ->with(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches', 'sponsor'])
            ->orderBy('agreement_reference')->orderBy('event_id')->orderBy('id')->paginate(20, pageName: 'sponsorHistoryPage');

        return view('livewire.sponsors.components.directory', ['promotion' => $promotion, 'sponsors' => $sponsors, 'selected' => $selected,
            'history' => $history, 'screen' => app(SponsorCommitmentsViewModel::class)]);
    }

    private function promotion(): Promotion
    {
        $promotion = Promotion::query()->findOrFail($this->promotionId);
        if (app(PromotionContextService::class)->isEnforced()) {
            abort_unless(app(PromotionContextService::class)->current()?->id === $promotion->id, 404);
        }

        return $promotion;
    }
}
