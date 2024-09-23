<x-card.general-info>
    <x-card.general-info.stat label="Start Date" :value="$referee->startedAt?->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
