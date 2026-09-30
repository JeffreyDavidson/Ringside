<x-card.general-info>
    <x-card.general-info.stat label="Status">
        {{ $referee->status->label() }}
        <x-availability-badges class="ms-2" :injured="$referee->isInjured()" :suspended="$referee->isSuspended()" />
    </x-card.general-info.stat>
    <x-card.general-info.stat
        label="Start Date"
        :value="$referee->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'"
    />
</x-card.general-info>
