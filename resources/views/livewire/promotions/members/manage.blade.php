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

    <div class="border-ringside-line grid gap-4 border-b px-5 py-5 lg:grid-cols-[minmax(0,1fr)_12rem] lg:px-6">
        <div>
            <label for="promotion-member-search" class="text-ringside-ink mb-2 block text-sm font-semibold">
                {{ __('promotions.add_member') }}
            </label>
            <x-form.input
                id="promotion-member-search"
                appearance="ringside"
                wire:model.live.debounce.250ms="search"
                placeholder="{{ __('promotions.search_global_users') }}"
                autocomplete="off"
                aria-describedby="promotion-member-search-help"
            />
            <p id="promotion-member-search-help" class="text-ringside-muted mt-2 text-xs">
                {{ __('promotions.search_global_users_help') }}
            </p>

            @error('userId')
                <p class="text-ringside-signal-soft mt-2 text-sm" role="alert">{{ $message }}</p>
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
                    <option value="{{ $role->value }}">{{ str($role->value)->headline() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if (mb_strlen(trim($search)) >= 2)
        <div class="border-ringside-line border-b px-5 py-4 lg:px-6" aria-live="polite">
            @if ($availableUsers->isEmpty())
                <p class="text-ringside-muted text-sm">{{ __('promotions.no_available_users') }}</p>
            @else
                <ul class="divide-ringside-line divide-y">
                    @foreach ($availableUsers as $user)
                        <li
                            wire:key="available-user-{{ $user->id }}"
                            class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <p class="text-ringside-ink truncate text-sm font-semibold">{{ $user->full_name }}</p>
                                <p class="text-ringside-muted truncate text-xs">{{ $user->email }}</p>
                            </div>
                            <x-button
                                variant="ringside"
                                size="sm"
                                wire:click="addMember({{ $user->id }})"
                                wire:loading.attr="disabled"
                                wire:target="addMember"
                            >
                                {{ __('promotions.add') }}
                            </x-button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if ($members->isEmpty())
        <div class="flex min-h-52 flex-col items-center justify-center px-6 py-10 text-center">
            <x-heroicon-o-user-group class="text-ringside-muted size-7" />
            <p class="text-ringside-ink mt-3 text-sm font-semibold">{{ __('promotions.no_members') }}</p>
            <p class="text-ringside-muted mt-1 max-w-sm text-sm">{{ __('promotions.no_members_help') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[38rem] text-left text-sm">
                <thead class="bg-ringside-surface-index text-ringside-muted border-ringside-line border-b text-xs tracking-[0.08em] uppercase">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold lg:px-6">{{ __('promotions.user') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('promotions.role') }}</th>
                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('promotions.membership_status') }}</th>
                        <th scope="col" class="px-5 py-3 text-right font-semibold lg:px-6">
                            {{ __('promotions.actions') }}
                        </th>
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
                                                    {{ str($role->value)->headline() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="submit"
                                            class="text-ringside-muted hover:text-ringside-white focus-visible:outline-ringside-white min-h-10 px-2 text-xs underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2"
                                        >
                                            {{ __('promotions.save_role') }}
                                        </button>
                                    </form>
                                    @error("memberRoles.{$membership->user_id}")
                                        <p class="text-ringside-signal-soft mt-1 text-xs" role="alert">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </td>
                                <td class="text-ringside-muted px-4 py-4">
                                    <span class="border-ringside-outline inline-flex min-h-7 items-center border px-2 text-xs">
                                        {{ str($membership->status->value)->headline() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right lg:px-6">
                                    @if ($membership->status === $activeStatus)
                                        <button
                                            type="button"
                                            wire:click="updateMemberStatus({{ $membership->user_id }}, '{{ $suspendedStatus->value }}')"
                                            wire:confirm="{{ __('promotions.confirm_suspend', ['name' => $membership->user->full_name]) }}"
                                            class="text-ringside-muted hover:text-ringside-signal-soft focus-visible:outline-ringside-white min-h-10 px-2 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2"
                                        >
                                            {{ __('promotions.suspend') }}
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="updateMemberStatus({{ $membership->user_id }}, '{{ $activeStatus->value }}')"
                                            class="text-ringside-ink hover:text-ringside-signal focus-visible:outline-ringside-white min-h-10 px-2 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2"
                                        >
                                            {{ __('promotions.reactivate') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
