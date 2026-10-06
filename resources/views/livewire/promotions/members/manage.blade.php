<section
    class="border-ringside-line bg-ringside-surface-panel min-w-0 border"
    aria-labelledby="promotion-members-title"
>
    <header class="border-ringside-line flex flex-wrap items-end justify-between gap-4 border-b px-5 py-4 lg:px-6">
        <div>
            <h2
                id="promotion-members-title"
                class="font-display text-ringside-ink text-2xl leading-none tracking-tight uppercase"
            >
                {{ __('promotions.members_title') }}
            </h2>
            <p class="text-ringside-muted mt-2 text-sm">
                {{ trans_choice('promotions.member_count', $members->count(), ['count' => $members->count()]) }}
            </p>
        </div>
        <a
            href="{{ route('promotions.index') }}"
            class="text-ringside-muted hover:text-ringside-white focus-visible:outline-ringside-white text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
        >
            {{ __('promotions.back_to_directory') }}
        </a>
    </header>

    @if ($canManageMembers)
        <form
            wire:submit="addMember"
            class="border-ringside-line grid gap-4 border-b px-5 py-5 lg:grid-cols-[minmax(0,1fr)_12rem_auto] lg:items-start lg:px-6"
        >
            <div>
                <label for="promotion-member-email" class="text-ringside-ink mb-2 block text-sm font-semibold">
                    {{ __('promotions.add_member') }}
                </label>
                <x-form.input
                    id="promotion-member-email"
                    type="email"
                    appearance="ringside"
                    wire:model="email"
                    placeholder="{{ __('promotions.member_email') }}"
                    autocomplete="off"
                    aria-describedby="promotion-member-email-help"
                />
                <p id="promotion-member-email-help" class="text-ringside-muted mt-2 text-xs">
                    {{ __('promotions.member_email_help') }}
                </p>

                @error('email')
                    <p id="promotion-member-email-error" class="text-ringside-signal-soft mt-2 text-sm" role="alert">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="new-member-role" class="text-ringside-ink mb-2 block text-sm font-semibold">
                    {{ __('promotions.role') }}
                </label>
                <select
                    id="new-member-role"
                    wire:model="newMemberRole"
                    class="border-ringside-outline bg-ringside-surface-panel text-ringside-ink focus-visible:outline-ringside-white min-h-14 w-full border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                >
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- The top margin matches the field labels so the button stays level with the inputs when help or error text wraps. --}}
            <x-button
                variant="ringside"
                type="submit"
                class="lg:mt-7 lg:min-h-14"
                wire:loading.attr="disabled"
                wire:target="addMember"
            >
                {{ __('promotions.add') }}
            </x-button>
        </form>

        @error('member')
            <p class="text-ringside-signal-soft border-ringside-line border-b px-5 py-3 text-sm lg:px-6" role="alert">
                {{ $message }}
            </p>
        @enderror
    @endif

    @if ($members->isEmpty())
        <div class="flex min-h-52 flex-col items-center justify-center px-6 py-10 text-center">
            <x-heroicon-o-user-group class="text-ringside-muted size-7" />
            <p class="text-ringside-ink mt-3 text-sm font-semibold">{{ __('promotions.no_members') }}</p>
            @if ($canManageMembers)
                <p class="text-ringside-muted mt-1 max-w-sm text-sm">{{ __('promotions.no_members_help') }}</p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[38rem] text-left text-sm">
                <thead class="bg-ringside-surface-index text-ringside-muted border-ringside-line border-b text-xs tracking-[0.08em] uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold lg:px-6">{{ __('promotions.user') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('promotions.role') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('promotions.membership_status') }}</th>
                        @if ($canManageMembers)
                            <th scope="col" class="px-5 py-3 text-right font-semibold lg:px-6">
                                {{ __('promotions.actions') }}
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-ringside-line divide-y">
                    @foreach ($members as $membership)
                        @if ($membership->user)
                            <tr
                                wire:key="promotion-member-{{ $membership->user_id }}"
                                class="hover:bg-ringside-surface-hover"
                            >
                                <td class="text-ringside-ink px-5 py-4 lg:px-6">
                                    <p class="font-semibold">{{ $membership->user->full_name }}</p>
                                    <p class="text-ringside-muted mt-1 text-xs">{{ $membership->user->email }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    @if ($canManageMembers)
                                        <form
                                            wire:submit="updateMemberRole({{ $membership->user_id }})"
                                            class="flex items-center gap-2"
                                        >
                                            <select
                                                wire:model="memberRoles.{{ $membership->user_id }}"
                                                aria-label="{{ __('promotions.role_for', ['name' => $membership->user->full_name]) }}"
                                                class="border-ringside-outline bg-ringside-surface-panel text-ringside-ink focus-visible:outline-ringside-white min-h-10 min-w-28 border px-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                                            >
                                                @foreach ($roles as $role)
                                                    <option
                                                        value="{{ $role->value }}"
                                                        @selected($role === $membership->role)
                                                    >
                                                        {{ $role->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="submit"
                                                wire:loading.attr="disabled"
                                                wire:target="updateMemberRole"
                                                class="text-ringside-muted hover:text-ringside-white focus-visible:outline-ringside-white min-h-10 px-2 text-xs whitespace-nowrap underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {{ __('promotions.save_role') }}
                                            </button>
                                        </form>
                                        @error("memberRoles.{$membership->user_id}")
                                            <p class="text-ringside-signal-soft mt-1 text-xs" role="alert">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    @else
                                        <span class="text-ringside-muted">{{ $membership->role->label() }}</span>
                                    @endif
                                </td>
                                <td class="text-ringside-muted px-4 py-4">
                                    <span class="border-ringside-outline inline-flex min-h-7 items-center border px-2 text-xs">
                                        {{ $membership->status->label() }}
                                    </span>
                                </td>
                                @if ($canManageMembers)
                                    <td class="px-5 py-4 text-right lg:px-6">
                                        @if ($membership->status === $activeStatus)
                                            <button
                                                type="button"
                                                wire:click="updateMemberStatus({{ $membership->user_id }}, '{{ $suspendedStatus->value }}')"
                                                wire:confirm="{{ __('promotions.confirm_suspend', ['name' => $membership->user->full_name]) }}"
                                                wire:loading.attr="disabled"
                                                wire:target="updateMemberStatus"
                                                class="text-ringside-muted hover:text-ringside-signal-soft focus-visible:outline-ringside-white min-h-10 px-2 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {{ __('promotions.suspend') }}
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="updateMemberStatus({{ $membership->user_id }}, '{{ $activeStatus->value }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="updateMemberStatus"
                                                class="text-ringside-ink hover:text-ringside-signal focus-visible:outline-ringside-white min-h-10 px-2 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {{ __('promotions.reactivate') }}
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Owners see pending invitations by the email they typed; nobody joins, and no account name appears, until the invitation is accepted. --}}
    @if ($canManageMembers && $invitations->isNotEmpty())
        <div class="border-ringside-line border-t" data-test="promotion-invitations">
            <h3 class="text-ringside-ink px-5 pt-5 pb-3 text-sm font-semibold lg:px-6">
                {{ __('promotions.invitations_title') }}
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[38rem] text-left text-sm">
                    <thead class="bg-ringside-surface-index text-ringside-muted border-ringside-line border-y text-xs tracking-[0.08em] uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold lg:px-6">
                                {{ __('promotions.invited_email') }}
                            </th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('promotions.role') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">
                                {{ __('promotions.invitation_expires_heading') }}
                            </th>
                            <th scope="col" class="px-4 py-3 font-semibold">
                                {{ __('promotions.membership_status') }}
                            </th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold lg:px-6">
                                {{ __('promotions.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-ringside-line divide-y">
                        @foreach ($invitations as $invitation)
                            <tr
                                wire:key="promotion-invitation-{{ $invitation->id }}"
                                class="hover:bg-ringside-surface-hover"
                            >
                                <td class="text-ringside-ink px-5 py-4 lg:px-6">{{ $invitation->email }}</td>
                                <td class="text-ringside-muted px-4 py-4">{{ $invitation->role->label() }}</td>
                                <td class="text-ringside-muted px-4 py-4" data-test="invitation-expires">
                                    {{ __('promotions.invitation_expires', ['date' => \App\Models\Promotions\Promotion::toLocalTime($promotion, $invitation->expires_at)->format('M j, Y')]) }}
                                </td>
                                <td class="text-ringside-muted px-4 py-4">
                                    <span class="border-ringside-outline inline-flex min-h-7 items-center border px-2 text-xs">
                                        {{ __('promotions.invitation_pending') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right lg:px-6">
                                    <button
                                        type="button"
                                        wire:click="cancelInvitation({{ $invitation->id }})"
                                        wire:confirm="{{ __('promotions.confirm_cancel_invitation') }}"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelInvitation"
                                        class="text-ringside-muted hover:text-ringside-signal-soft focus-visible:outline-ringside-white min-h-10 px-2 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{ __('promotions.cancel_invitation') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
