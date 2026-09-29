<x-card.general-info>
    <livewire:components.lifecycle-status :model="$referee" updatedEvent="referee-updated" />
    <x-card.general-info.stat
        label="Start Date"
        :value="$referee->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'"
    />
</x-card.general-info>
