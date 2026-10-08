<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')">
        {{ $manager->status->label() }}
        <x-availability-badges class="ms-2" :injured="$manager->isInjured()" :suspended="$manager->isSuspended()" />
    </x-card.general-info.stat>
    @if ($manager->currentWrestlers->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_wrestlers')">
            @foreach ($manager->currentWrestlers as $wrestler)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('wrestlers.show', $wrestler)" :label="$wrestler->name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($manager->currentTagTeams->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_tag_teams')">
            @foreach ($manager->currentTagTeams as $tagTeam)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('tag-teams.show', $tagTeam)" :label="$tagTeam->name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    <x-card.general-info.stat
        :label="__('core.general_info.start_date')"
        :value="$manager->firstEmployment?->started_at->toDateString() ?? __('core.general_info.no_start_date_set')"
    />
</x-card.general-info>
