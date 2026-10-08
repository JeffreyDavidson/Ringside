<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')" :value="$stable->status->label()" />
    @if ($stable->currentWrestlers->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_wrestlers')">
            @foreach ($stable->currentWrestlers as $wrestler)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('wrestlers.show', $wrestler)" :label="$wrestler->name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($stable->currentTagTeams->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_tag_teams')">
            @foreach ($stable->currentTagTeams as $tagTeam)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('tag-teams.show', $tagTeam)" :label="$tagTeam->name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    <x-card.general-info.stat
        :label="__('core.general_info.start_date')"
        :value="$stable->firstActivityPeriod?->started_at->toDateString() ?? __('core.general_info.no_start_date_set')"
    />
</x-card.general-info>
