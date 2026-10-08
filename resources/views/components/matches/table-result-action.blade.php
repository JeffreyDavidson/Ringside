@php
    $canEdit = $row->match_finish === null && auth()->user()?->can('update', $row);
    $canRemove = auth()->user()?->can('delete', $row);
@endphp

<div class="flex items-center justify-end gap-1">
    <x-buttons.light
        size="sm"
        class="whitespace-nowrap"
        data-test="match-result-action"
        wire:click="$dispatch('openModal', { component: 'matches.modals.result-modal', arguments: { matchId: {{ $row->id }} } })"
    >
        {{ $row->match_finish === null ? __('matches.actions.record_result') : __('matches.actions.correct_result') }}
    </x-buttons.light>

    @if ($canEdit || $canRemove)
        <x-tables.row-actions-menu
            :label="__('matches.actions.menu', ['number' => $row->match_number])"
            :menu-label="__('matches.actions.menu_label')"
        >
            @if ($canEdit)
                <li class="m-0 flex flex-col p-0">
                    <button
                        type="button"
                        class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                        data-test="match-edit-action"
                        data-match-id="{{ $row->id }}"
                        aria-label="{{ __('matches.actions.edit') }} {{ $row->match_number }}"
                        x-on:click="open = false"
                        wire:click="$dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {{ $row->event_id }}, modelId: {{ $row->id }} } })"
                    >
                        <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                        <span>{{ __('matches.actions.edit') }}</span>
                    </button>
                </li>
            @endif
            @if ($canEdit && $canRemove)
                <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
            @endif
            @if ($canRemove)
                <li class="m-0 flex flex-col p-0">
                    <button
                        type="button"
                        class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                        data-test="match-delete-action"
                        aria-label="{{ __('matches.actions.remove_match', ['number' => $row->match_number]) }}"
                        x-on:click="open = false"
                        wire:click="delete({{ $row->id }})"
                        wire:confirm="{{ __('matches.actions.confirm_remove', ['number' => $row->match_number]) }}"
                    >
                        <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                        <span>{{ __('matches.actions.remove') }}</span>
                    </button>
                </li>
            @endif
        </x-tables.row-actions-menu>
    @endif
</div>
