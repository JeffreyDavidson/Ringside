<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\StorylineData;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateAction
{
    public function __construct(private readonly RecordRevisionAction $revision, private readonly PromotionContextService $context) {}

    public function handle(Promotion $promotion, StorylineData $data): Storyline
    {
        Gate::authorize('create', Storyline::class);
        abort_if($this->context->isEnforced() && $this->context->current()?->id !== $promotion->id, 403);
        Validator::make(['title' => $data->title, 'premise' => $data->premise, 'payoff' => $data->payoff, 'submission_id' => $data->submissionId], [
            'title' => ['required', 'string', 'max:160'], 'premise' => ['required', 'string', 'max:4000'],
            'payoff' => ['nullable', 'string', 'max:4000'], 'submission_id' => ['required', 'uuid'],
        ])->validate();

        return DB::transaction(function () use ($promotion, $data): Storyline {
            Promotion::query()->whereKey($promotion->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('create', Storyline::class);
            $existing = Storyline::query()->where('submission_id', $data->submissionId)->first();
            if ($existing !== null) {
                abort_unless($existing->promotion_id === $promotion->id, 403);

                return $existing;
            }
            $story = new Storyline;
            $story->promotion_id = $promotion->id;
            $story->title = mb_trim($data->title);
            $story->premise = $data->premise;
            $story->payoff = $data->payoff;
            $story->submission_id = $data->submissionId;
            $story->save();
            $story->refresh();
            $this->revision->handle($story, $story, 'created');

            return $story;
        }, attempts: 3);
    }
}
