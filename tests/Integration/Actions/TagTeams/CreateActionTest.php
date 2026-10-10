<?php

declare(strict_types=1);

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeams\TagTeamData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\TagTeams\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;

test('it creates a new tag team', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'The Test Team',
        signature_move: 'Double Suplex',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->name)->toBe('The Test Team')
        ->and($tagTeam->signature_move)->toBe('Double Suplex');

    // Verify tag team was created in database
    assertDatabaseHas('tag_teams', [
        'name' => 'The Test Team',
        'signature_move' => 'Double Suplex',
    ]);
});

test('it creates tag team with minimal data', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Minimal Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->name)->toBe('Minimal Team')
        ->and($tagTeam->signature_move)->toBeNull();

    assertDatabaseHas('tag_teams', [
        'name' => 'Minimal Team',
        'signature_move' => null,
    ]);
});

test('it creates partnerships for both wrestlers', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Partnership Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    // Verify partnerships were created
    assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerA->id,
        'left_at' => null,
    ]);

    assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestlerB->id,
        'left_at' => null,
    ]);

    expect($tagTeam->wrestlers()->count())->toBe(2);
});

test('it handles database transactions correctly', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Transaction Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->exists)->toBeTrue()
        ->and($tagTeam->wrestlers()->count())->toBe(2);

    // Verify all related records were created atomically
    $wrestlers = $tagTeam->wrestlers;
    expect($wrestlers->contains($wrestlerA))->toBeTrue()
        ->and($wrestlers->contains($wrestlerB))->toBeTrue();
});

test('it receives validated wrestlers in its data', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Invalid Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    expect($data->wrestlerA)->toBe($wrestlerA)
        ->and($data->wrestlerB)->toBe($wrestlerB);
});

test('it creates tag team with all optional fields', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Full Data Team',
        signature_move: 'Ultimate Finisher',
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    expect($tagTeam->name)->toBe('Full Data Team')
        ->and($tagTeam->signature_move)->toBe('Ultimate Finisher')
        ->and($tagTeam->wrestlers()->count())->toBe(2);
});

test('it creates partnerships with correct timestamps', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();

    $data = new TagTeamData(
        name: 'Timestamp Team',
        signature_move: null,
        employment_date: null,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
    );

    $tagTeam = resolve(CreateAction::class)->handle($data);

    // Check partnerships have current timestamp
    $partnerships = $tagTeam->wrestlers()->get();
    foreach ($partnerships as $wrestler) {
        $membership = TagTeamWrestler::query()
            ->whereBelongsTo($tagTeam, 'tagTeam')
            ->whereBelongsTo($wrestler)
            ->firstOrFail();

        expect($membership->joined_at)->not->toBeNull()
            ->and($membership->left_at)->toBeNull();
    }
});

test('it employs the tag team and its founding members', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $employmentDate = now()->subDay();

    $tagTeam = resolve(CreateAction::class)->handle(new TagTeamData(
        name: 'Employed Team',
        signature_move: null,
        employment_date: $employmentDate,
        wrestlerA: $wrestlerA,
        wrestlerB: $wrestlerB,
        managers: new Collection([$manager]),
    ));

    expect($tagTeam->currentEmployment()->exists())->toBeTrue()
        ->and($wrestlerA->currentEmployment()->exists())->toBeTrue()
        ->and($wrestlerB->currentEmployment()->exists())->toBeTrue()
        ->and($manager->currentEmployment()->exists())->toBeTrue();
});

function newTagTeamData(string $name, ?string $signatureMove = null): TagTeamData
{
    return new TagTeamData(
        name: $name,
        signature_move: $signatureMove,
        employment_date: null,
        wrestlerA: Wrestler::factory()->create(),
        wrestlerB: Wrestler::factory()->create(),
    );
}

describe('tag team name guard', function (): void {
    test('it locks the name and the signature move of the promotion before inserting the tag team', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $data = newTagTeamData('  The Kings ', 'Royal Flush');
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

        // Assert
        $lockPositions = array_keys(array_filter($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"')));
        $insertPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "tag_teams"'));

        expect(array_map(fn (int $position): array => $statements[$position]['bindings'], $lockPositions))->toBe([
            [$lock->key(GuardedName::TagTeamName, $promotion->id, 'The Kings')],
            [$lock->key(GuardedName::TagTeamSignatureMove, $promotion->id, 'Royal Flush')],
        ])
            ->and(array_last($lockPositions))->toBeLessThan($insertPosition);
    });

    test('it takes no signature move lock for a tag team without one', function () {
        // Arrange
        $data = newTagTeamData('The Kings');

        // Act
        resolve(CreateAction::class)->handle($data);

        // Assert
        expect(DB::table('record_name_locks')->count())->toBe(1);
    });

    test('it rejects a name another tag team of the promotion already uses, even with surrounding space or deleted', function (string $name, bool $deleted) {
        // Arrange
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);
        $existing = TagTeam::factory()->for($promotion, 'promotion')->create(['name' => 'The Kings']);

        if ($deleted) {
            $existing->delete();
        }

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(newTagTeamData($name));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A tag team named 'The Kings' already exists in this promotion.")
            ->and(TagTeam::query()->withoutGlobalScopes()->where('name', 'The Kings')->count())->toBe(1);
    })->with([
        'exact' => ['The Kings', false],
        'leading space' => [' The Kings', false],
        'deleted' => ['The Kings', true],
    ]);

    test('it rejects a signature move another tag team of the promotion already uses', function () {
        // Arrange
        TagTeam::factory()->create(['name' => 'The Kings', 'signature_move' => 'Royal Flush']);

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(newTagTeamData('The Queens', 'Royal Flush'));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A tag team with the signature move 'Royal Flush' already exists in this promotion.")
            ->and(TagTeam::query()->where('name', 'The Queens')->exists())->toBeFalse();
    });

    test('it allows a name and signature move only another promotion uses', function () {
        // Arrange
        TagTeam::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'The Kings', 'signature_move' => 'Royal Flush']);
        $promotion = Promotion::factory()->create();
        enforcePromotionContext($promotion);

        // Act
        $tagTeam = resolve(CreateAction::class)->handle(newTagTeamData('The Kings', 'Royal Flush'));

        // Assert
        expect($tagTeam->promotion_id)->toBe($promotion->id);
    });
});
