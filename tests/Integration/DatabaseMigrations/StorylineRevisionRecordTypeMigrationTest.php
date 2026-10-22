<?php

declare(strict_types=1);

use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineRevision;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

function runRevisionRecordTypeMigration(): void
{
    $migration = require database_path('migrations/2026_10_22_150819_map_storyline_revision_record_types_to_short_names.php');
    $migration->up();
}

test('it rewrites each old class name to its short name and leaves the other columns alone', function (string $old, string $short) {
    // Arrange
    $story = Storyline::factory()->create();
    $actor = User::factory()->create();
    $id = DB::table('storyline_revisions')->insertGetId([
        'storyline_id' => $story->id,
        'promotion_id' => $story->promotion_id,
        'actor_id' => $actor->id,
        'operation' => 'story_revised',
        'record_type' => $old,
        'record_id' => 7,
        'reason' => 'A revised direction.',
        'before' => '{"a":1}',
        'after' => '{"b":2}',
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-02 10:00:00',
    ]);
    $before = (array) DB::table('storyline_revisions')->find($id);

    // Act
    runRevisionRecordTypeMigration();

    // Assert
    $after = (array) DB::table('storyline_revisions')->find($id);

    expect($after['record_type'])->toBe($short)
        ->and([...$after, 'record_type' => $old])->toEqual($before);
})->with([
    'storyline' => ['Storyline', 'storyline'],
    'step' => ['StorylineStep', 'step'],
    'participant' => ['StorylineParticipant', 'participant'],
    'development' => ['StorylineDevelopment', 'development'],
]);

test('it leaves a value it does not know and rows already migrated as they are', function () {
    // Arrange
    $story = Storyline::factory()->create();
    $unknownId = DB::table('storyline_revisions')->insertGetId([
        'storyline_id' => $story->id,
        'promotion_id' => $story->promotion_id,
        'actor_id' => User::factory()->create()->id,
        'operation' => 'story_revised',
        'record_type' => 'Wrestler',
        'record_id' => 7,
        'before' => '[]',
        'after' => '[]',
    ]);
    $migrated = StorylineRevision::factory()->for($story)->create(['record_type' => 'step']);

    // Act
    runRevisionRecordTypeMigration();

    // Assert
    expect(DB::table('storyline_revisions')->where('id', $unknownId)->value('record_type'))->toBe('Wrestler')
        ->and(DB::table('storyline_revisions')->where('id', $migrated->id)->value('record_type'))->toBe('step');
});
