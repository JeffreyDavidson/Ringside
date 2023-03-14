<?php

use App\Builders\WrestlerQueryBuilder;
use App\Enums\WrestlerStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanJoinTagTeams;
use App\Models\Concerns\HasManagers;
use App\Models\Concerns\HasMatches;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\TagTeamMember;
use App\Models\SingleRosterMember;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a wrestler has a name', function () {
    $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler Name']);

    expect($wrestler)->name->toBe('Example Wrestler Name');
});

test('a wrestler has a height', function () {
    $wrestler = Wrestler::factory()->create(['height' => 70]);

    expect($wrestler)->height->toBe(70);
});

test('a wrestler has a weight', function () {
    $wrestler = Wrestler::factory()->create(['weight' => 210]);

    expect($wrestler)->weight->toBe(210);
});

test('a wrestler has a hometown', function () {
    $wrestler = Wrestler::factory()->create(['hometown' => 'Los Angeles, California']);

    expect($wrestler)->hometown->toBe('Los Angeles, California');
});

test('a wrestler can have a signature move', function () {
    $wrestler = Wrestler::factory()->create(['signature_move' => 'Example Signature Move']);

    expect($wrestler)->signature_move->toBe('Example Signature Move');
});

test('a wrestler has a status', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler)->status->toBeInstanceOf(WrestlerStatus::class);
});

test('a wrestler is unemployed by default', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler)->status->toMatchObject(WrestlerStatus::UNEMPLOYED);
});

test('a wrestler is a single roster member', function () {
    expect(get_parent_class(Wrestler::class))->toBe(SingleRosterMember::class);
});

test('a wrestler implements bookable interface', function () {
    expect(class_implements(Wrestler::class))->toContain(Bookable::class);
});

test('a wrestler implements can be a stable member interface', function () {
    expect(class_implements(Wrestler::class))->toContain(CanBeAStableMember::class);
});

test('a wrestler implements manageable interface', function () {
    expect(class_implements(Wrestler::class))->toContain(Manageable::class);
});

test('a wrestler implements tag team member interface', function () {
    expect(class_implements(Wrestler::class))->toContain(TagTeamMember::class);
});

test('a wrestler uses can join stables trait', function () {
    expect(Wrestler::class)->usesTrait(CanJoinStables::class);
});

test('a wrestler uses can join tag teams trait', function () {
    expect(Wrestler::class)->usesTrait(CanJoinTagTeams::class);
});

test('a wrestler uses has managers trait', function () {
    expect(Wrestler::class)->usesTrait(HasManagers::class);
});

test('a wrestler uses can have matches trait', function () {
    expect(Wrestler::class)->usesTrait(HasMatches::class);
});

test('a wrestler uses owned by user trait', function () {
    expect(Wrestler::class)->usesTrait(OwnedByUser::class);
});

test('a wrestler uses has factory trait', function () {
    expect(Wrestler::class)->usesTrait(HasFactory::class);
});

test('a wrestler uses soft deleted trait', function () {
    expect(Wrestler::class)->usesTrait(SoftDeletes::class);
});

test('a wrestler has its own eloquent builder', function () {
    expect(new Wrestler())->query()->toBeInstanceOf(WrestlerQueryBuilder::class);
});

test('a wrestler has a display name', function () {
    $wrestler = Wrestler::factory()->create(['name' => 'Hulk Hogan']);

    expect($wrestler)->displayName->toBe('Hulk Hogan');
});
