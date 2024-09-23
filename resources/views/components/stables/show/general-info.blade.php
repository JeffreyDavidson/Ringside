<x-card.general-info>
    @if ($stable->currentWrestlers->isNotEmpty())
        <x-card.general-info.links label="Current Wrestler(s)">
            @foreach ($stable->currentWrestlers as $wrestler)
                <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($stable->currentTagTeams->isNotEmpty())
        <x-card.general-info.links label="Current Tag Team(s)">
            @foreach ($stable->currentTagTeams as $tagTeam)
                <x-route-link :route="route('tag-teams.show', $tagTeam)" label="{{ $tagTeam->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($stable->currentManagers->isNotEmpty())
        <x-card.general-info.links label="Current Manager(s)">
            @foreach ($stable->currentManagers as $manager)
                <x-route-link :route="route('managers.show', $manager)" label="{{ $manager->full_name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    <x-card.general-info.stat label="Start Date" :value="$stable->startedAt?->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
