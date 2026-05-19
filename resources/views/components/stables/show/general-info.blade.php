<x-card.general-info>
    @if ($stable->currentWrestlers->isNotEmpty())
        <x-card.general-info.link-list label="Current Wrestler(s)">
            @foreach ($stable->currentWrestlers as $wrestler)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    @if ($stable->currentTagTeams->isNotEmpty())
        <x-card.general-info.link-list label="Current Tag Team(s)">
            @foreach ($stable->currentTagTeams as $tagTeam)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('tag-teams.show', $tagTeam)" label="{{ $tagTeam->name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif
    <x-card.general-info.stat label="Start Date" :value="$stable->firstActivation?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
