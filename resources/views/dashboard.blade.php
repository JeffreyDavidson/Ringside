<x-layouts.app :title="__('dashboard.title')">
    <x-layouts.workspace-canvas class="flex flex-col gap-7.5">
        <x-layouts.table-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle')" />

        <x-dashboard.section
            :title="__('dashboard.roster')"
            :link="route('wrestlers.index')"
            :link-label="__('dashboard.roster_link')"
            data-test="dashboard-roster"
        >
            <dl class="border-border bg-surface-header m-0 grid grid-cols-2 border lg:grid-cols-4">
                @foreach (['available', 'injured', 'suspended', 'employed'] as $status)
                    <div @class([
                        'border-border flex flex-col gap-2 px-5 py-5',
                        'border-s' => ! $loop->first,
                        'border-t lg:border-t-0' => $loop->index >= 2,
                        'max-lg:border-s-0' => $loop->index === 2,
                    ])>
                        <dt class="text-muted-foreground flex items-center gap-2 text-xs font-bold tracking-[0.08em] uppercase">
                            @if ($status === 'available')
                                <span class="bg-signal size-1.5" aria-hidden="true"></span>
                            @endif
                            {{ __("dashboard.{$status}") }}
                        </dt>
                        <dd class="font-display text-foreground m-0 text-4xl leading-none">
                            {{ $availability[$status] }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-dashboard.section>

        <div class="grid grid-cols-1 gap-7.5 xl:grid-cols-[1.25fr_1fr]">
            <x-dashboard.section
                :title="__('dashboard.upcoming_events')"
                :link="route('events.index')"
                :link-label="__('dashboard.events_link')"
                data-test="dashboard-upcoming-events"
            >
                @if ($upcomingEvents->isEmpty())
                    <p class="border-border bg-surface-header text-muted-foreground m-0 border px-5 py-5 text-sm">
                        {{ __('dashboard.no_upcoming_events') }}
                    </p>
                @else
                    <ol class="border-border bg-surface-header m-0 list-none border p-0">
                        @foreach ($upcomingEvents as $event)
                            <li @class(['border-border flex items-center gap-5 px-5 py-4', 'border-t' => ! $loop->first])>
                                <div class="border-border flex w-14 shrink-0 flex-col items-center border-e pe-5 text-center">
                                    <span class="text-signal-soft text-xs font-bold tracking-[0.08em] uppercase">
                                        {{ $event->local_date?->format('M') }}
                                    </span>
                                    <span class="font-display text-foreground text-3xl leading-none">{{ $event->local_date?->format('j') }}</span>
                                </div>
                                <div class="flex min-w-0 flex-col gap-1">
                                    <a
                                        href="{{ route('events.show', $event) }}"
                                        class="text-foreground decoration-border-strong hover:decoration-signal focus-visible:outline-foreground truncate font-semibold underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                    >{{ $event->name }}</a>
                                    <span class="text-muted-foreground text-sm">
                                        {{ $event->local_date?->format('D, M j · g:i A') }} · {{ $event->venue->name ?? __('dashboard.no_venue') }}
                                    </span>
                                </div>
                                <span class="text-muted-foreground ms-auto shrink-0 text-sm">
                                    {{ trans_choice('dashboard.match_count', $event->matches_count) }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-dashboard.section>

            <x-dashboard.section
                :title="__('dashboard.current_champions')"
                :link="route('titles.index')"
                :link-label="__('dashboard.titles_link')"
                data-test="dashboard-current-champions"
            >
                @if ($champions === [])
                    <p class="border-border bg-surface-header text-muted-foreground m-0 border px-5 py-5 text-sm">
                        {{ __('dashboard.no_champions') }}
                    </p>
                @else
                    <ul class="border-border bg-surface-header m-0 list-none border p-0">
                        @foreach ($champions as $champion)
                            @php($championship = $champion['championship'])
                            <li @class(['border-border flex flex-col gap-1 px-5 py-4', 'border-t' => ! $loop->first])>
                                <a
                                    href="{{ route('titles.show', $champion['title']) }}"
                                    class="text-muted-foreground hover:text-foreground focus-visible:outline-foreground text-xs font-bold tracking-[0.08em] uppercase focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $champion['title']->name }}</a>
                                <a
                                    href="{{ $champion['url'] }}"
                                    class="text-foreground decoration-border-strong hover:decoration-signal focus-visible:outline-foreground font-semibold underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $championship->champion->name }}</a>
                                <span class="text-muted-foreground text-sm">
                                    {{
                                        trans_choice('dashboard.champion_since', $champion['days'], [
                                            'date' => $championship->local_won_at->format('M j, Y'),
                                            'days' => $champion['days'],
                                        ])
                                    }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.section>
        </div>
    </x-layouts.workspace-canvas>
</x-layouts.app>
