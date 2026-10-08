<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')">
        {{ $wrestler->status->label() }}
        <x-availability-badges class="ms-2" :injured="$wrestler->isInjured()" :suspended="$wrestler->isSuspended()" />
    </x-card.general-info.stat>
    <x-card.general-info.stat :label="__('core.general_info.height')" :value="$wrestler->height" />
    <x-card.general-info.stat :label="__('core.general_info.weight')" :value="$wrestler->weight" />
    <x-card.general-info.stat :label="__('core.general_info.hometown')" :value="$wrestler->hometown" />
    @if ($wrestler->signature_move)
        <x-card.general-info.stat :label="__('core.general_info.signature_move')" :value="$wrestler->signature_move" />
    @endif
    @if ($wrestler->currentTagTeam)
        <x-card.general-info.links :label="__('core.general_info.current_tag_team')">
            <x-route-link
                :route="route('tag-teams.show', $wrestler->currentTagTeam)"
                :label="$wrestler->currentTagTeam->name"
            />
        </x-card.general-info.links>
    @endif
    @if ($wrestler->currentManagers->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_managers')">
            @foreach ($wrestler->currentManagers as $manager)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('managers.show', $manager)" :label="$manager->full_name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($wrestler->currentStable)
        <x-card.general-info.links :label="__('core.general_info.current_stable')">
            <x-route-link
                :route="route('stables.show', $wrestler->currentStable)"
                :label="$wrestler->currentStable->name"
            />
        </x-card.general-info.links>
    @endif
    @if ($wrestler->currentChampionships->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_title_championships')">
            @foreach ($wrestler->currentChampionships as $currentChampionship)
                <x-card.general-info.link-item>
                    <x-route-link
                        :route="route('titles.show', $currentChampionship->title)"
                        :label="$currentChampionship->title->name"
                    />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    <x-card.general-info.stat
        :label="__('core.general_info.start_date')"
        :value="$wrestler->firstEmployment?->started_at->toDateString() ?? __('core.general_info.no_start_date_set')"
    />
</x-card.general-info>
