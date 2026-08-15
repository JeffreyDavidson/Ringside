<x-buttons.light
    size="sm"
    data-test="match-result-action"
    wire:click="$dispatch('openModal', { component: 'matches.modals.result-modal', arguments: { matchId: {{ $row->id }} } })"
>
    {{ $row->match_finish === null ? 'Record Result' : 'Correct Result' }}
</x-buttons.light>
