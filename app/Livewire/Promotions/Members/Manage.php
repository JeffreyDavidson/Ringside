<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Members;

use App\Actions\Promotions\AddPromotionMemberAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Exceptions\BaseBusinessException;
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

    public string $email = '';

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

    public function addMember(): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $validated = Validator::make(
            ['email' => $this->email, 'role' => $this->newMemberRole],
            [
                'email' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::enum(MembershipRole::class)],
            ],
        )->validate();

        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($validated['email']))])
            ->where('status', UserStatus::Active)
            ->first();

        $role = MembershipRole::from($validated['role']);

        if ($user === null || ! app(AddPromotionMemberAction::class)->handle($promotion, $user, $role)) {
            $this->addError('email', __('promotions.member_not_added'));

            return;
        }

        $this->memberRoles[$user->id] = $role->value;
        $this->reset('email');
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

        try {
            app(UpdatePromotionMemberRoleAction::class)->handle(
                $promotion,
                $user,
                $role,
            );
        } catch (BaseBusinessException $exception) {
            $this->memberRoles[$userId] = $this->currentRole($promotion, $userId);
            $this->addError('member', $exception->getMessage());
        }
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

        try {
            app(UpdatePromotionMemberStatusAction::class)->handle(
                $promotion,
                $user,
                MembershipStatus::from($status),
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('member', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $promotion = $this->promotion();
        Gate::authorize('view', $promotion);

        $members = PromotionMembership::query()
            ->where('promotion_id', $promotion->getKey())
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $canManageMembers = Gate::allows('manageMembers', $promotion);

        return view('livewire.promotions.members.manage', [
            'members' => $members,
            'canManageMembers' => $canManageMembers,
            'roles' => MembershipRole::cases(),
            'activeStatus' => MembershipStatus::Active,
            'suspendedStatus' => MembershipStatus::Suspended,
        ]);
    }

    /** @param array<string, mixed> $params */
    public function placeholder(array $params = []): View
    {
        return view('livewire.promotions.members.loading-placeholder');
    }

    private function promotion(): Promotion
    {
        return Promotion::query()->findOrFail($this->promotionId);
    }

    private function currentRole(Promotion $promotion, int $userId): string
    {
        return $promotion->memberships()
            ->where('user_id', $userId)
            ->firstOrFail()
            ->role
            ->value;
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
