<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\StorylineData;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Lifecycle\Storylines\StorylineLock;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(private readonly StorylineLock $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, StorylineData $data, string $reason, User $actor): void
    {
        DB::transaction(function () use ($storyline, $version, $data, $reason, $actor): void {
            $story = $this->lock->lock($storyline, $version);
            $before = $story->attributesToArray();
            $story->title = mb_trim($data->title);
            $story->premise = $data->premise;
            $story->payoff = $data->payoff;
            $story->save();
            $this->revision->handle($story, $story, StorylineRevisionOperation::StoryRevised, $actor, $before, $reason);
        }, attempts: 3);
    }
}
