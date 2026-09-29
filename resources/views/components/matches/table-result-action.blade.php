<div class="flex flex-col items-start gap-2">
    @can('update', $row)
        @if ($row->match_finish === null)
            <x-buttons.light
                size="sm"
                data-test="match-edit-action"
                data-match-id="{{ $row->id }}"
                aria-label="{{ __('matches.actions.edit') }} {{ $row->match_number }}"
                wire:click="$dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {{ $row->event_id }}, modelId: {{ $row->id }} } })"
            >
                {{ __('matches.actions.edit') }}
            </x-buttons.light>
        @endif
    @endcan

    <x-buttons.light
        size="sm"
        data-test="match-result-action"
        wire:click="$dispatch('openModal', { component: 'matches.modals.result-modal', arguments: { matchId: {{ $row->id }} } })"
    >
        {{ $row->match_finish === null ? 'Record Result' : 'Correct Result' }}
    </x-buttons.light>

    @can('delete', $row)
        <x-buttons.danger
            size="sm"
            data-test="match-delete-action"
            aria-label="{{ __('matches.actions.remove_match', ['number' => $row->match_number]) }}"
            wire:click="delete({{ $row->id }})"
            wire:confirm="{{ __('matches.actions.confirm_remove', ['number' => $row->match_number]) }}"
        >
            {{ __('matches.actions.remove') }}
        </x-buttons.danger>
    @endcan
</div>
