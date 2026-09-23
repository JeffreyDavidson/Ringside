<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Members;

use App\Actions\Promotions\AddPromotionMemberAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** @property-read Collection<int, PromotionMembership> $members */
class Manage extends Component
{
    #[Locked]
    public int $promotionId;

    public string $search = '';

    public string $newMemberRole = MembershipRole::Member->value;

    /** @var array<int, string> */
    public array $memberRoles = [];

    public function mount(int $promotionId): void
    {
        $this->promotionId = $promotionId;

        Gate::authorize('view', $this->promotion());

        $this->memberRoles = PromotionMembership::query()
            ->where('promotion_id', $this->promotionId)
            ->get(['user_id', 'role'])
            ->mapWithKeys(fn (PromotionMembership $membership): array => [
                $membership->user_id => $membership->role->value,
            ])
            ->all();
    }

    public function addMember(int $userId): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        Validator::make(
            ['userId' => $userId, 'role' => $this->newMemberRole],
            [
                'userId' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')
                        ->where('status', UserStatus::Active->value)
                        ->whereNull('deleted_at'),
                ],
                'role' => ['required', Rule::enum(MembershipRole::class)],
            ],
        )->validate();

        $user = User::query()->whereKey($userId)->firstOrFail();
        $role = MembershipRole::from($this->newMemberRole);

        app(AddPromotionMemberAction::class)->handle(
            $promotion,
            $user,
            $role,
        );

        $this->memberRoles[$user->id] = $role->value;
        $this->reset('search');
    }

    public function updateMemberRole(int $userId): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $user = $this->memberUser($promotion, $userId);
        Validator::make(
            ['role' => $this->memberRoles[$userId] ?? null],
            ['role' => ['required', Rule::enum(MembershipRole::class)]],
        )->validate();

        $role = MembershipRole::from($this->memberRoles[$userId]);

        app(UpdatePromotionMemberRoleAction::class)->handle(
            $promotion,
            $user,
            $role,
        );
    }

    public function updateMemberStatus(int $userId, string $status): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $user = $this->memberUser($promotion, $userId);
        Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::in([
                MembershipStatus::Active->value,
                MembershipStatus::Suspended->value,
            ])]],
        )->validate();

        app(UpdatePromotionMemberStatusAction::class)->handle(
            $promotion,
            $user,
            MembershipStatus::from($status),
        );
    }

    public function render(): View
    {
        $promotion = $this->promotion();

        $members = PromotionMembership::query()
            ->where('promotion_id', $promotion->getKey())
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $availableUsers = collect();
        $search = trim($this->search);

        if (mb_strlen($search) >= 2) {
            $availableUsers = User::query()
                ->where('status', UserStatus::Active)
                ->whereDoesntHave('promotions', function (Builder $query) use ($promotion): void {
                    $query->whereKey($promotion->getKey());
                })
                ->where(function (Builder $query) use ($search): void {
                    $normalizedSearch = mb_strtolower($search);
                    $searchPattern = "%{$normalizedSearch}%";

                    $query->whereRaw('LOWER(full_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchPattern]);
                })
                ->orderBy('full_name')
                ->limit(8)
                ->get();
        }

        return view('livewire.promotions.members.manage', [
            'members' => $members,
            'availableUsers' => $availableUsers,
            'roles' => MembershipRole::cases(),
            'activeStatus' => MembershipStatus::Active,
            'suspendedStatus' => MembershipStatus::Suspended,
        ]);
    }

    private function promotion(): Promotion
    {
        return Promotion::query()->findOrFail($this->promotionId);
    }

    private function memberUser(Promotion $promotion, int $userId): User
    {
        return User::query()
            ->whereHas('promotionMemberships', function (Builder $query) use ($promotion): void {
                $query->where('promotion_id', $promotion->getKey());
            })
            ->whereKey($userId)
            ->firstOrFail();
    }
}
