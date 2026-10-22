<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveParticipantAction;
use App\Data\Storylines\ParticipantData;
use App\Enums\Storylines\StorylineStatus;
use App\Livewire\Storylines\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('story search and archive filters preserve clear empty states and independent pagination', function () {
    actingAs(administrator());
    $active = Storyline::factory()->create(['title' => 'Harbor Rivalry']);
    Storyline::factory()->create(['title' => 'Hidden Past', 'status' => StorylineStatus::Archived]);
    $page = livewire(Main::class);
    $page->assertSee('Harbor Rivalry')->assertDontSee('Hidden Past');
    $page->set('status', 'archived')->assertSee('Hidden Past')->assertDontSee('Harbor Rivalry');
    $page->set('status', 'invalid')->set('search', 'Harbor')->assertSee('Harbor Rivalry');
    $page->set('search', 'Absent')->assertSee('No storylines match');
});
test('participant search is scoped to the loaded story summaries', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create(['title' => 'Harbor Rivalry']);
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Harbor Rival']);
    resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, 'Rival', today()->toDateString()), signedInUser());
    livewire(Main::class, ['participant' => 'Harbor'])->assertSee('Harbor Rivalry');
    livewire(Main::class, ['participant' => 'Foreign'])->assertDontSee('Harbor Rivalry');
});
