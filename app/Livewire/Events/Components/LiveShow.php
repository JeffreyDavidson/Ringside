<?php

declare(strict_types=1);

namespace App\Livewire\Events\Components;

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\LiveShowReviewData;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Services\Events\LiveShowTimingService;
use App\ViewModels\LiveShowViewModel;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LiveShow extends Component
{
    #[Locked]
    public int $eventId;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public int $packVersion = 0;

    #[Locked]
    public string $fingerprint = '';

    #[Locked]
    public int $draftVersion = 0;

    #[Locked]
    public string $draftKey = '';

    #[Locked]
    public string $draftOperation = '';

    #[Locked]
    public string $draftLabel = '';

    #[Locked]
    public int $reviewPackVersion = 0;

    #[Locked]
    public string $reviewFingerprint = '';

    #[Locked]
    public bool $reviewing = false;

    #[Locked]
    public string $refreshedAt = '';

    public string $reason = '';

    public string $actualStart = '';

    public string $actualFinish = '';

    public string $startOffset = '';

    public string $finishOffset = '';

    public string $rosterFilter = 'all';

    /** @var array<string, string> */
    public array $removedReasons = [];

    public function mount(int $eventId): void
    {
        $this->eventId = $eventId;
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $event = $this->event();
        Gate::authorize('view', $event);
        $this->refreshedAt = now()->utc()->toIso8601String();
    }

    public function prepare(): void
    {
        $this->perform(fn (Event $event): EventLiveRun => app(PrepareLiveShowAction::class)->handle($event, $this->version, $this->packVersion, $this->fingerprint));
    }

    public function attendance(string $key, string $mark, bool $value): void
    {
        Validator::make(['mark' => $mark], ['mark' => [Rule::enum(LiveAttendanceMark::class)]])->validate();
        $this->perform(fn (Event $event) => app(MarkLiveAttendanceAction::class)->handle($event, $this->version, $key, LiveAttendanceMark::from($mark), $value));
    }

    public function item(string $key, string $operation): void
    {
        Validator::make(['operation' => $operation], ['operation' => [Rule::in(['start', 'finish'])]])->validate();
        $this->perform(fn (Event $event) => app(TransitionLiveItemAction::class)->handle($event, $this->version, $key, LiveItemOperation::from($operation)));
    }

    public function beginCorrection(string $key, string $operation): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        Validator::make(['operation' => $operation], ['operation' => [Rule::in(['skip', 'correct', 'reopen'])]])->validate();
        $run = EventLiveRun::query()->where('event_id', $event->id)->firstOrFail();
        $item = collect($run->content['items'])->firstWhere('key', $key);
        if ($item === null) {
            $this->addError('run', __('live-show.unknown_item'));

            return;
        }
        $this->reviewing = false;
        $this->draftKey = $key;
        $this->draftLabel = $item['label'];
        $this->draftOperation = $operation;
        $this->draftVersion = $run->version;
        $this->reason = '';
        $zone = $run->content['timezone'];
        $start = $item['start'] === null ? null : Carbon::parse($item['start'])->setTimezone($zone);
        $finish = $item['finish'] === null ? null : Carbon::parse($item['finish'])->setTimezone($zone);
        $this->actualStart = $start?->format('Y-m-d\TH:i') ?? '';
        $this->actualFinish = $finish?->format('Y-m-d\TH:i') ?? '';
        $this->startOffset = $start?->format('P') ?? now()->setTimezone($zone)->format('P');
        $this->finishOffset = $finish?->format('P') ?? $this->startOffset;
        $this->resetErrorBag();
        $this->dispatch('live-draft-opened');
    }

    public function saveCorrection(): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $rules = ['reason' => ['required', 'string', 'max:2000'], 'draftOperation' => [Rule::in(['skip', 'correct', 'reopen'])]];
        if ($this->draftOperation === 'correct') {
            $rules += ['actualStart' => ['required', 'date_format:Y-m-d\TH:i'], 'actualFinish' => ['nullable', 'date_format:Y-m-d\TH:i'],
                'startOffset' => ['required', 'regex:/^[+-]\d{2}:\d{2}$/'], 'finishOffset' => ['required', 'regex:/^[+-]\d{2}:\d{2}$/']];
        }
        Validator::make(['draftOperation' => $this->draftOperation, 'reason' => $this->reason, 'actualStart' => $this->actualStart, 'actualFinish' => $this->actualFinish,
            'startOffset' => $this->startOffset, 'finishOffset' => $this->finishOffset], $rules)->validate();
        $this->perform(function (Event $event): void {
            $run = EventLiveRun::query()->where('event_id', $event->id)->firstOrFail();
            $timing = app(LiveShowTimingService::class);
            $start = $this->draftOperation === 'correct' ? $timing->wallTime($this->actualStart, $run->content['timezone'], $this->startOffset) : null;
            $finish = $this->actualFinish === '' ? null : $timing->wallTime($this->actualFinish, $run->content['timezone'], $this->finishOffset);
            if ($this->draftOperation === 'correct' && ($start === null || ($this->actualFinish !== '' && $finish === null))) {
                throw LiveShowException::invalidTimes();
            }
            app(TransitionLiveItemAction::class)->handle($event, $this->draftVersion, $this->draftKey,
                LiveItemOperation::from($this->draftOperation), $this->reason, $start, $finish);
        });
    }

    public function beginReview(): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $data = app(LiveShowViewModel::class)->data($event);
        $this->reviewPackVersion = $data['source']['pack']->version ?? 0;
        $this->reviewFingerprint = $data['source']['fingerprint'] ?? '';
        $this->draftVersion = $data['run']->version ?? 0;
        $this->draftKey = '';
        $this->removedReasons = [];
        $this->reviewing = true;
    }

    public function acceptReview(): void
    {
        Validator::make(['removedReasons' => $this->removedReasons], ['removedReasons' => ['array'], 'removedReasons.*' => ['required', 'string', 'max:2000']])->validate();
        $this->perform(fn (Event $event) => app(ReviewLiveShowAction::class)->handle($event, $this->draftVersion,
            $this->reviewPackVersion, $this->reviewFingerprint, LiveShowReviewData::fromValidated($this->removedReasons)));
    }

    public function cancelDraft(): void
    {
        Gate::authorize('view', $this->event());
        $this->draftKey = '';
        $this->reviewing = false;
        $this->resetErrorBag();
    }

    public function complete(): void
    {
        $this->perform(fn (Event $event) => app(CompleteLiveShowAction::class)->handle($event, $this->version));
    }

    /** @param Closure(Event): mixed $operation */
    private function perform(Closure $operation): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $this->resetErrorBag();
        try {
            $operation($event);
            $this->draftKey = '';
            $this->reviewing = false;
        } catch (BaseBusinessException $exception) {
            $this->addError('run', $exception->getMessage());
        }
        $this->refreshState();
    }

    public function render(): View
    {
        $event = $this->event();
        Gate::authorize('view', $event);
        $data = app(LiveShowViewModel::class)->data($event, $this->refreshedAt);
        $this->version = $data['run']->version ?? 0;
        $this->packVersion = $data['source']['pack']->version ?? 0;
        $this->fingerprint = $data['source']['fingerprint'] ?? '';

        return view('livewire.events.components.live-show', $data);
    }

    private function event(): Event
    {
        return Event::query()->findOrFail($this->eventId);
    }
}
