@can('manageUsers', \App\Models\Users\User::class)
    <li class="m-0 flex flex-col p-0">
        <button
            class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink group m-0 mx-2.5 flex grow cursor-pointer items-center p-2.5"
            x-on:click="open = false"
            wire:click="changeStatus({{ $rowId }}, '{{ $statusAction['status']->value }}')"
            @if ($statusAction['status'] === \App\Enums\Users\UserStatus::Inactive) wire:confirm="Deactivate this user account?" @endif
        >
            <span class="me-2.5 flex shrink-0 items-center">
                @if ($statusAction['status'] === \App\Enums\Users\UserStatus::Inactive)
                    <x-heroicon-m-no-symbol class="size-5" />
                @else
                    <x-heroicon-m-check-circle class="size-5" />
                @endif
            </span>
            <span class="flex grow items-center text-sm font-medium">{{ $statusAction['label'] }}</span>
        </button>
    </li>
@endcan
