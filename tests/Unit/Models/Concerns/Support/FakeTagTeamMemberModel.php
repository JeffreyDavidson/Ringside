<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanJoinTagTeams;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fake model for testing CanJoinTagTeams trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
#[Table('fake_tag_team_members')]
#[Fillable('name')]
class FakeTagTeamMemberModel extends Model
{
    use CanJoinTagTeams;

    /**
     * Static method to override tag team pivot model class for testing.
     */
    public static function fakeTagTeamPivotModel(?string $modelClass): void
    {
        static::$fakeTagTeamPivotModelClass = $modelClass;
    }

    private static ?string $fakeTagTeamPivotModelClass = null;

    public function resolveTagTeamPivotModel(): string
    {
        return static::$fakeTagTeamPivotModelClass ?? FakeTagTeamPivotModel::class;
    }
}
