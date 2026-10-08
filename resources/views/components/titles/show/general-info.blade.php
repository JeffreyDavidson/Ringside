@props(['title'])

<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.type')" :value="$title->type->label()" />
    <x-card.general-info.stat :label="__('core.general_info.status')" :value="$title->status->label()" />
    <x-card.general-info.links :label="__('core.general_info.current_champion')">
        @if ($title->currentChampionship)
            <x-route-link
                :route="$title->currentChampionship->champion instanceof \App\Models\Roster\Wrestlers\Wrestler
                    ? route('wrestlers.show', $title->currentChampionship->champion)
                    : route('tag-teams.show', $title->currentChampionship->champion)"
                :label="$title->currentChampionship->champion->name"
            />
        @else
            Vacant
        @endif
    </x-card.general-info.links>
    <x-card.general-info.stat
        :label="__('core.general_info.date_introduced')"
        :value="$title->firstActivityPeriod?->started_at->toDateString() ?? __('core.general_info.no_start_date_set')"
    />
</x-card.general-info>
