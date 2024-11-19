<x-card.general-info>
    <x-card.general-info.stat label="Start Date" :value="$referee->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
