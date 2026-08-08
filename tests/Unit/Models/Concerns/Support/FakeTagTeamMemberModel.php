<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanJoinTagTeams;
use App\Models\Contracts\CanBeATagTeamMember;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake model for testing CanJoinTagTeams trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 *
 * @implements CanBeATagTeamMember<FakeTagTeamPivotModel, self>
 */
#[Table('fake_tag_team_members')]
#[Fillable('name')]
class FakeTagTeamMemberModel extends Model implements CanBeATagTeamMember
{
    /** @use CanJoinTagTeams<FakeTagTeamPivotModel, self> */
    use CanJoinTagTeams;

    /**
     * Static method to override tag team pivot model class for testing.
     */
    public static function fakeTagTeamPivotModel(?string $modelClass): void
    {
        self::$fakeTagTeamPivotModelClass = $modelClass;
    }

    private static ?string $fakeTagTeamPivotModelClass = null;

    public function resolveTagTeamPivotModel(): string
    {
        return self::$fakeTagTeamPivotModelClass ?? FakeTagTeamPivotModel::class;
    }
}
