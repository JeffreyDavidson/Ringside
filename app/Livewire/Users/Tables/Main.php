<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Actions\Users\ChangeStatusAction;
use App\Builders\Users\UserBuilder;
use App\Enums\Users\UserStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** @extends BaseTable<User> */
class Main extends BaseTable
{
    use DispatchesActionFeedback;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'users';

    #[\Override]
    protected string $routeBasePath = 'users';

    #[\Override]
    protected string $resourceName = 'users';

    /** @return UserBuilder<User> */
    public function builder(): UserBuilder
    {
        return User::query()
            ->select('*')
            ->oldest('last_name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getActionColumnViewData(Model $row): array
    {
        $viewData = parent::getActionColumnViewData($row);

        if (! $row instanceof User) {
            return $viewData;
        }

        $statusAction = match ($row->status) {
            UserStatus::Unverified => ['label' => 'Activate account', 'status' => UserStatus::Active],
            UserStatus::Active => ['label' => 'Deactivate account', 'status' => UserStatus::Inactive],
            UserStatus::Inactive => ['label' => 'Reactivate account', 'status' => UserStatus::Active],
        };

        return [
            ...$viewData,
            'additionalActionsView' => 'components.tables.columns.user-status-action',
            'statusAction' => $statusAction,
        ];
    }

    public function changeStatus(int $userId, string $status, ChangeStatusAction $changeStatusAction): void
    {
        $targetStatus = UserStatus::tryFrom($status);

        if ($targetStatus === null) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid user status.',
            ]);
        }

        $user = User::query()->findOrFail($userId);

        Gate::authorize('manageUsers', User::class);

        $changeStatusAction->handle($user, $targetStatus);

        $this->dispatchActionSuccess("User account status changed to {$targetStatus->label()}.");
    }

    /** @return array<Column> */
    public function columns(): array
    {
        return [
            Column::make(__('users.name'), 'full_name')
                ->searchable(function (UserBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('users.role'), 'role')
                ->label(fn (User $row) => $row->role->name),
            Column::make(__('core.status'), 'status')
                ->label(fn (User $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
            Column::make(__('users.phone'), 'phone_number')
                ->label(fn (User $row, Column $column): string => $row->phone_number?->formatted() ?? ''),
        ];
    }

    /** @return array<int, Filter> */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->options(UserStatus::filterOptions())
                ->filter(function (UserBuilder $builder, string $value): void {
                    $status = UserStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereStatus($status);
                    }
                }),
        ];
    }
}
