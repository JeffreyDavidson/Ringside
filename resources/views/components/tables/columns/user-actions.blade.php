<x-tables.row-actions-menu :label="'Actions for '.$user->full_name" menu-label="User actions">
    @can('view', $user)
        <li class="m-0 flex flex-col p-0">
            <a
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
                x-on:click="open = false"
                href="{{ route('users.show', $user) }}"
            >
                <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
                <span>View</span>
            </a>
        </li>
    @endcan
    @can('update', $user)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="$dispatch('openModal', { component: 'users.modals.form-modal', arguments: { modelId: {{ $user->id }} } })"
            >
                <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                <span>Edit</span>
            </button>
        </li>
    @endcan
    @can('manageUsers', \App\Models\Users\User::class)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="changeStatus({{ $user->id }}, '{{ $statusAction['status']->value }}')"
                @if ($statusAction['status'] === \App\Enums\Users\UserStatus::Inactive) wire:confirm="{{ __('core.lifecycle_confirmations.deactivate', ['name' => $user->full_name]) }}" @endif
            >
                @if ($statusAction['status'] === \App\Enums\Users\UserStatus::Inactive)
                    <x-heroicon-m-no-symbol class="text-ringside-muted size-5" aria-hidden="true" />
                @else
                    <x-heroicon-m-check-circle class="text-ringside-muted size-5" aria-hidden="true" />
                @endif
                <span>{{ $statusAction['label'] }}</span>
            </button>
        </li>
    @endcan
</x-tables.row-actions-menu>
