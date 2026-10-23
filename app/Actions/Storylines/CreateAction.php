<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\StorylineData;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\RecordChangedException;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(private readonly RecordRevisionAction $revision) {}

    public function handle(Promotion $promotion, StorylineData $data, User $actor): Storyline
    {
        return DB::transaction(function () use ($promotion, $data, $actor): Storyline {
            $promotion->refreshForUpdate();

            $existing = Storyline::query()->where('submission_id', $data->submissionId)->first();

            if ($existing !== null) {
                if ($existing->promotion_id !== $promotion->id) {
                    throw RecordChangedException::changed();
                }

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
            $this->revision->handle($story, $story, StorylineRevisionOperation::Created, $actor);

            return $story;
        }, attempts: 3);
    }
}
