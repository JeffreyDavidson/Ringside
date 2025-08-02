<x-card.general-info>
    @if ($manager->currentWrestlers->isNotEmpty())
        <x-card.general-info.link-list label="Current Wrestler(s)">
            @foreach ($manager->currentWrestlers as $wrestler)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($manager->currentTagTeams->isNotEmpty())
        <x-card.general-info.link-list label="Current Tag Team(s)">
            @foreach ($manager->currentTagTeams as $tagTeam)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('tag-teams.show', $tagTeam)" label="{{ $tagTeam->name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($manager->currentStable)
        <x-card.general-info.links label="Current Stable">
            <x-route-link :route="route('stables.show', $manager->currentStable)" label="{{ $manager->currentStable->name }}" />
        </x-card.general-info.links>
    @endif
    <x-card.general-info.stat label="Start Date" :value="$manager->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
