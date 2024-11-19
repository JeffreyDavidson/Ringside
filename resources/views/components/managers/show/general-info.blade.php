<x-card.general-info>
    @if ($manager->currentWrestlers->isNotEmpty())
        <x-card.general-info.links label="Current Wrestler(s)">
            @foreach ($manager->currentWrestlers as $wrestler)
                <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($manager->currentTagTeams->isNotEmpty())
        <x-card.general-info.links label="Current Tag Team(s)">
            @foreach ($manager->currentTagTeams as $tagTeam)
                <x-route-link :route="route('tag-teams.show', $tagTeam)" label="{{ $tagTeam->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($manager->currentStable)
        <x-card.general-info.links label="Current Stable">
            <x-route-link :route="route('stables.show', $manager->currentStable)" label="{{ $manager->currentStable->name }}" />
        </x-card.general-info.links>
    @endif
    <x-card.general-info.stat label="Start Date" :value="$manager->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
