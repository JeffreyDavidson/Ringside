<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Actions\Users\ChangeStatusAction;
use App\Builders\Users\UserBuilder;
use App\Enums\Users\UserStatus;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PendingInvitationSummaryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** @extends BaseTable<User> */
class Main extends BaseTable
{
    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $modelClass = User::class;

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
            ->oldest('last_name')
            ->oldest('id');
    }

    /** @var array<string, string> Pending invitation summaries by normalized email, for the unverified users on this page. */
    protected array $pendingInvitationSummaries = [];

    /** @param Collection<int, User> $rows */
    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $this->pendingInvitationSummaries = resolve(PendingInvitationSummaryService::class)->forEmails(
            $rows->where('status', UserStatus::Unverified)->map(fn (User $user): string => $user->email)->all(),
        );
    }

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (User $row) => view('components.tables.columns.user-actions', [
                'user' => $row,
                'statusAction' => $this->statusActionFor($row),
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array{label: string, status: UserStatus, confirmation: ?string}
     */
    private function statusActionFor(User $user): array
    {
        return match ($user->status) {
            UserStatus::Unverified => [
                'label' => 'Activate account',
                'status' => UserStatus::Active,
                'confirmation' => $this->activationConfirmationFor($user),
            ],
            UserStatus::Active => ['label' => 'Deactivate account', 'status' => UserStatus::Inactive, 'confirmation' => null],
            UserStatus::Inactive => ['label' => 'Reactivate account', 'status' => UserStatus::Active, 'confirmation' => null],
        };
    }

    /** Activation lets the account accept every pending invitation for its email, so the administrator is told which. */
    private function activationConfirmationFor(User $user): ?string
    {
        $invitations = $this->pendingInvitationSummaries[PromotionInvitation::normalizeEmail($user->email)] ?? null;

        if ($invitations === null) {
            return null;
        }

        return __('users.activation_confirmation', ['name' => $user->full_name, 'invitations' => $invitations]);
    }

    public function changeStatus(int $userId, string $status, ChangeStatusAction $changeStatusAction): void
    {
        Gate::authorize('manageUsers', User::class);

        $targetStatus = UserStatus::tryFrom($status);

        if ($targetStatus === null) {
            throw ValidationException::withMessages([
                'status' => __('users.invalid_status'),
            ]);
        }

        $user = User::query()->findOrFail($userId);

        try {
            $changeStatusAction->handle($user, $targetStatus);
        } catch (BaseBusinessException $exception) {
            $this->dispatchActionFailure($exception->getMessage());

            return;
        }

        $this->forgetMetadata();
        $this->dispatchActionSuccess(__('users.status_changed', ['status' => $targetStatus->label()]));
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
            SelectFilter::make(__('core.status'), 'status')
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
