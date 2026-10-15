<x-layouts.app :title="__('production.sheet')">
    <div class="mx-auto flex max-w-5xl flex-col gap-6">
        <nav class="flex flex-wrap gap-4">
            <a
                class="min-h-11 py-3 underline"
                href="{{ route('events.pack.show', $sheet['event']) }}"
                >{{ __('event-pack.back_to_pack') }}</a
            ><a
                class="min-h-11 py-3 underline"
                href="{{ route('events.live-show.show', $sheet['event']) }}"
                >{{ __('live-show.title') }}</a
            ><a
                class="min-h-11 py-3 underline"
                href="{{ route('events.production-sheet.show', ['event' => $sheet['event'], 'source' => $sheet['source'], 'person' => $sheet['person'], 'role' => $sheet['role'], 'print' => 1]) }}"
                >{{ __('production.print') }}</a>
        </nav>
        <header>
            <h1 class="font-display text-3xl">{{ $sheet['eventName'] }} · {{ __('production.title') }}</h1>
            <p class="text-muted-foreground mt-3">
                {{ __('production.source.'.$sheet['source']) }} · {{ $sheet['version'] > 0 ? __('production.version', ['version' => $sheet['version']]) : __('event-pack.not_saved') }} · {{ $sheet['timezone'] }}
            </p>
        </header>
        @if ($sheet['needsReview'])
            <p role="status" class="border-warning text-warning border p-4">{{ __('production.source_review') }}</p>
        @endif
        <nav class="flex flex-wrap gap-4">
            <a href="#cue-sheet" class="min-h-11 py-3 underline">{{ __('production.cues') }}</a
            ><a href="#crew-call-sheet" class="min-h-11 py-3 underline">{{ __('production.call_sheet') }}</a>
        </nav>
        <details>
            <summary class="min-h-11 cursor-pointer py-3">
                {{ __('production.filter_options') }}
                @if ($sheet['person']) ·{{ collect($sheet['allAssignments'])->firstWhere('contact_id', $sheet['person'])['name'] ?? __('production.not_assigned') }} @endif
                @if ($sheet['role']) ·{{ \App\Enums\Crew\CrewRole::tryFrom($sheet['role'])?->label() ?? $sheet['role'] }} @endif
            </summary>
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <label class="flex flex-col gap-2"
                    >{{ __('production.source_label') }}<select
                        name="source"
                        class="border-border bg-surface min-h-11 border p-3"
                    >
                        <option value="live" @disabled(! $sheet['hasLive']) @selected($sheet['source'] === 'live')>
                            {{ __('production.source.live') }}
                        </option>
                        <option value="plan" @selected($sheet['source'] === 'plan')>
                            {{ __('production.source.plan') }}
                        </option>
                    </select></label>
                <label class="flex min-w-0 flex-col gap-2"
                    >{{ __('production.person_filter') }}<select
                        name="person"
                        class="border-border bg-surface min-h-11 min-w-0 border p-3"
                    >
                        <option value="">{{ __('production.everyone') }}</option>
                        @foreach (collect($sheet['allAssignments'])->unique('contact_id') as $person)
                            <option
                                value="{{ $person['contact_id'] }}"
                                @selected($sheet['person'] === $person['contact_id'])
                            >
                                {{ $person['name'] }}
                            </option>
                        @endforeach</select
                ></label>
                <label class="flex flex-col gap-2"
                    >{{ __('production.role') }}<select
                        name="role"
                        class="border-border bg-surface min-h-11 border p-3"
                    >
                        <option value="">{{ __('production.any_role') }}</option>
                        @foreach (\App\Enums\Crew\CrewRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected($sheet['role'] === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach</select
                ></label>
                <x-button type="submit">{{ __('production.apply_filters') }}</x-button>
            </form>
        </details>
        <p class="text-muted-foreground">{{ __('production.filter_help') }}</p>
        <section id="cue-sheet">
            <h2 class="text-xl font-semibold">{{ __('production.cue_sheet') }}</h2>
            @foreach ($sheet['rows'] as $row)
                <article class="border-border border-t py-5">
                    <h3 class="text-xl font-semibold break-words">{{ $row['label'] }}</h3>
                    <p class="text-muted-foreground mt-2">
                        {{ str_replace('T', ' ', $row['timing']['revised_at'] ?? $row['timing']['planned_at'] ?? __('production.not_set')) }}
                    </p>
                    <x-events.production.cues :cues="$row['cues']" :assignments="$sheet['allAssignments']" />
                    @if (! $row['cues'])
                        <p class="text-muted-foreground mt-3">{{ __('production.no_cues') }}</p>
                    @endif
                </article>
            @endforeach
        </section>
        <section id="crew-call-sheet">
            <h2 class="text-xl font-semibold">{{ __('production.call_sheet') }}</h2>
            @forelse ($sheet['assignments'] as $assignment)
                <article class="border-border border-t py-5">
                    <h3 class="text-lg font-semibold break-words">
                        {{ $assignment['name'] }} · {{ \App\Enums\Crew\CrewRole::tryFrom($assignment['role'])?->label() ?? $assignment['role'] }}
                    </h3>
                    <p class="mt-2 break-words">{{ $assignment['station'] }}</p>
                    <p class="mt-2">
                        {{ __('production.call_time') }}: {{ str_replace('T', ' ', $assignment['call_at'] ?? __('production.not_set')) }} · {{ $assignment['timezone'] }} {{ $assignment['offset'] }}
                    </p>
                    @foreach (['setup', 'during', 'strike'] as $phase)
                        @if ($assignment[$phase])
                            <p class="mt-2 break-words whitespace-pre-wrap">
                                {{ __('production.'.$phase) }}: {{ $assignment[$phase] }}
                            </p>
                        @endif
                    @endforeach
                </article>
            @empty
                <p class="text-muted-foreground py-5">{{ __('production.empty_assignments') }}</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
