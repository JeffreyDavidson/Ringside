<?php

declare(strict_types=1);
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Actions\Storylines\SaveStepAction;
use App\Data\Events\EventPackData;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineStep;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/** @return array{story: Storyline, event: Event, match: EventMatch, run: EventLiveRun, segment: string} */
function storylineSourceFixture(): array
{
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
    $story = Storyline::factory()->for($promotion)->create(['title' => 'Private betrayal']);
    $event = Event::factory()->for($promotion)->create(['date' => now(), 'name' => 'Harbor Show']);
    $cast = Wrestler::factory()->count(2)->for($promotion)->bookable()->create();
    $match = EventMatch::factory()->for($event)->withCompetitors($cast->all())->create();
    $pack = resolve(EventPackViewModel::class)->data($event);
    $segment = '1f64fc86-5bbf-4a41-952d-b85800358f00';
    $content = $pack['content'];
    $content['segments'] = [['id' => $segment, 'label' => 'Closing promo', 'after_match_id' => $match->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => 10, 'notes' => null]];
    resolve(SaveEventPackAction::class)->handle($event, 0, $pack['card']['fingerprint'], EventPackData::fromValidated($content), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $pack['card']['fingerprint'])->refresh();

    return ['story' => $story, 'event' => $event, 'match' => $match, 'run' => $run, 'segment' => $segment];
}
function plannedStorylineStep(Storyline $story, ?SourceData $source = null): StorylineStep
{
    return resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, new StepData('Planned betrayal', 'Attack the partner.', 1, $source ?? new SourceData, (string) Str::uuid()), signedInUser());
}
function confirmedStorylineDevelopment(Storyline $story, ?int $stepId = null): StorylineDevelopment
{
    return resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, new DevelopmentData('Actual confrontation', 'They argued without attacking.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid(), $stepId), signedInUser());
}
