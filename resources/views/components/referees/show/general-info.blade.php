<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')">
        {{ $referee->status->label() }}
        <x-availability-badges class="ms-2" :injured="$referee->isInjured()" :suspended="$referee->isSuspended()" />
    </x-card.general-info.stat>
    <x-card.general-info.stat
        :label="__('core.general_info.start_date')"
        :value="$referee->firstEmployment?->started_at->toDateString() ?? __('core.general_info.no_start_date_set')"
    />
</x-card.general-info>
