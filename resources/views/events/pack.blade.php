<x-layouts.app :title="__('event-pack.title')">
    <x-layouts.workspace-canvas class="event-pack-editor">
        <a class="pack-back" href="{{ route('events.show', $event) }}">&larr; {{ __('event-pack.back') }}</a>
        <a class="pack-back" href="{{ route('events.live-show.show', $event) }}">{{ __('live-show.title') }}</a>
        @can('manageSponsors', $event)
            <a class="pack-back" href="{{ route('events.sponsors.show', $event) }}">{{ __('sponsors.title') }}</a>
        @endcan
        <header class="pack-heading">
            <div>
                <h1>{{ $event->name }}</h1>
                <p class="pack-purpose">{{ __('event-pack.title') }}</p>
                <p>
                    {{ $event->local_date?->format('M j, Y · H:i') ?? __('event-pack.unscheduled') }} · {{ $card['timezone'] }}
                </p>
                <p>{{ $event->venue?->name ?? __('event-pack.no_venue') }}</p>
            </div>
            <a
                class="pack-button"
                data-print-saved
                data-discard-message="{{ __('event-pack.discard_prompt') }}"
                href="{{ route('events.pack.show', ['event' => $event, 'print' => 1]) }}"
            >{{ __('event-pack.preview') }}</a>
        </header>
        <nav class="pack-nav" aria-label="{{ __('event-pack.title') }}">
            <a href="#running-order">{{ __('event-pack.running_order') }} <span>{{ count($card['rows']) }}</span></a>
            @can('viewProduction', $event)
                <a href="#production">{{ __('production.title') }}</a>
            @endcan
            <a href="#show-details">{{ __('event-pack.show_details') }}</a>
            <a href="#segments">{{ __('event-pack.segments') }}</a>
            <a href="#pack-notes">{{ __('event-pack.notes') }}</a>
            <a href="#roster">{{ __('event-pack.roster') }} <span>{{ count($card['roster']) }}</span></a>
        </nav>
        @session('success')
            <p role="status" class="pack-notice pack-success">{{ $value }}</p>
        @endsession
        @if ($errors->any())
            <div class="pack-error" role="alert">
                <strong
                    ><x-heroicon-o-exclamation-circle class="pack-alert-icon" aria-hidden="true" />
                    {{ __('event-pack.save_failed') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('events.pack.show', $event) }}">{{ __('event-pack.reload') }}</a>
            </div>
        @endif
        @if ($needsReview)
            <p class="pack-notice">{{ __('event-pack.review_notice') }}</p>
        @endif
        @if ($content['timezone'] !== $card['timezone'])
            <p class="pack-notice">
                {{ __('event-pack.zone_notice', ['saved' => $content['timezone'], 'current' => $card['timezone']]) }}
            </p>
        @endif
        <p class="pack-help">{{ __('event-pack.instructions') }}</p>
        <form
            method="POST"
            action="{{ route('events.pack.update', $event) }}"
            data-pack-form
            data-dirty-message="{{ __('event-pack.unsaved') }}"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="version" value="{{ old('version', $pack?->version ?? 0) }}" />
            <input type="hidden" name="fingerprint" value="{{ old('fingerprint', $card['fingerprint']) }}" />
            <input type="hidden" name="timezone" value="{{ old('timezone', $content['timezone']) }}" />
            <fieldset @disabled(! auth()->user()->can('update', $event))>
                <div class="pack-workspace">
                    <div class="pack-main">
                        <section class="pack-section" id="running-order">
                            <h2>
                                {{ __('event-pack.running_order') }}
                                <span class="pack-count">{{ count($card['rows']) }}</span>
                            </h2>
                            @php($entries = collect(old('matches', $content['matches']))->keyBy('match_id'))
                            @forelse ($card['rows'] as $row)
                                <article class="pack-entry">
                                    <h3>
                                        <span class="pack-order">{{ $row['number'] }}</span
                                        ><span>{{ $row['label'] ?: __('event-pack.no_competitors') }}</span>
                                    </h3>
                                    <p>{{ $row['details'] }}</p>
                                    <p class="pack-help">
                                        {{ __('event-pack.referee') }}: {{ $row['referees'] ?: __('event-pack.unassigned') }}
                                    </p>
                                    @if ($row['warning'])
                                        <p class="pack-notice">{{ __('event-pack.unavailable') }}</p>
                                    @endif
                                    <input
                                        type="hidden"
                                        name="matches[{{ $row['id'] }}][match_id]"
                                        value="{{ $row['id'] }}"
                                    />
                                    <x-events.pack.timing
                                        :prefix="'matches['.$row['id'].']'"
                                        :entry="$entries->get($row['id'], [])"
                                    />
                                </article>
                            @empty
                                <p class="pack-empty">{{ __('event-pack.empty_card') }}</p>
                            @endforelse
                            @if ($removed !== [])
                                <details class="pack-entry">
                                    <summary>
                                        {{ __('event-pack.removed_entries', ['count' => count($removed)]) }}
                                    </summary>
                                    @foreach ($removed as $entry)
                                        <p>
                                            {{ __('event-pack.match_id', ['id' => $entry['match_id']]) }}: {{ $entry['planned_at'] }} · {{ $entry['notes'] }}
                                        </p>
                                    @endforeach
                                </details>
                            @endif
                        </section>
                        <section class="pack-section" id="segments">
                            <h2>{{ __('event-pack.segments') }}</h2>
                            <p class="pack-help">{{ __('event-pack.segment_help') }}</p>
                            <input type="hidden" name="segments" value="" />
                            <div data-segments>
                                @foreach ((old('segments', $content['segments']) ?? []) as $index => $entry)
                                    <x-events.pack.segment :$index :$entry :$card />
                                @endforeach
                            </div>
                            <button class="pack-button" type="button" data-add-segment>
                                {{ __('event-pack.add_segment') }}
                            </button>
                            <template data-segment-template
                                ><x-events.pack.segment index="__INDEX__" :$card
                            /></template>
                        </section>
                        <section class="pack-section" id="pack-notes">
                            <h2>{{ __('event-pack.notes') }}</h2>
                            <x-events.pack.field
                                name="notes"
                                :label="__('event-pack.general_notes')"
                                type="textarea"
                                :value="old('notes', $content['notes'])"
                                :max="10000"
                            />
                            <x-events.pack.field
                                name="follow_up"
                                :label="__('event-pack.follow_up')"
                                type="textarea"
                                :value="old('follow_up', $content['follow_up'])"
                                :max="10000"
                            />
                        </section>
                    </div>
                    <aside class="pack-sidebar">
                        <section class="pack-section" id="show-details">
                            <h2>{{ __('event-pack.show_details') }}</h2>
                            <p class="pack-help">
                                {{ __('event-pack.time_help', ['timezone' => old('timezone', $content['timezone'])]) }}
                            </p>
                            <div class="pack-timings">
                                <x-events.pack.field
                                    name="doors_at"
                                    :label="__('event-pack.doors')"
                                    type="datetime-local"
                                    :value="old('doors_at', $content['doors_at'])"
                                />
                                <x-events.pack.field
                                    name="revision"
                                    :label="__('event-pack.revision')"
                                    :value="old('revision', $content['revision'])"
                                    :max="40"
                                />
                                <x-events.pack.field
                                    name="master"
                                    :label="__('event-pack.master')"
                                    :value="old('master', $content['master'])"
                                    :max="120"
                                />
                            </div>
                        </section>
                        <section class="pack-section" id="roster">
                            <h2>
                                {{ __('event-pack.roster') }}
                                <span class="pack-count">{{ count($card['roster']) }}</span>
                            </h2>
                            <p class="pack-help">{{ __('event-pack.membership_note') }}</p>
                            @forelse ($card['roster'] as $person)
                                <p class="pack-roster-person">
                                    <strong>{{ $person['name'] }}</strong>
                                    · {{ $person['role'] }} · {{ __('event-pack.matches') }} {{ implode(', ', $person['matches']) }}
                                    @if ($person['warning'])
                                        <span>{{ __('event-pack.unavailable') }}</span>
                                    @endif
                                </p>
                            @empty
                                <p class="pack-empty">{{ __('event-pack.empty_roster') }}</p>
                            @endforelse
                        </section>
                    </aside>
                </div>
                @can('update', $event)
                    @if ($needsReview)
                        <label class="pack-review"
                            ><input type="checkbox" name="reviewed" value="1" required />
                            {{ __('event-pack.reviewed') }}</label>
                    @endif
                    <div class="pack-savebar">
                        <span
                            role="status"
                            data-save-state
                        >{{ $errors->any() ? __('event-pack.unsaved') : ($pack ? __('event-pack.last_saved', ['time' => $pack->updated_at->timezone($content['timezone'])->format('M j, Y H:i').' '.$content['timezone']]) : __('event-pack.not_saved')) }}</span>
                        <button class="pack-button pack-primary" type="submit">{{ __('event-pack.save') }}</button>
                        <button class="pack-button" type="submit" name="print_after" value="1">
                            {{ __('event-pack.save_preview') }}
                        </button>
                    </div>
                @endcan
            </fieldset>
            <input type="hidden" name="complete" value="1" />
        </form>
        @if (($content['sponsor_tasks'] ?? []) !== [])
            <section class="pack-section" id="sponsor-setup">
                <h2>{{ __('sponsors.setup_title') }}</h2>
                <p>{{ __('sponsors.plan_notice') }}</p>
                @foreach ($content['sponsor_tasks'] as $task)
                    <div class="pack-row">
                        <h3>{{ $task['label'] }}</h3>
                        <p class="break-words whitespace-pre-wrap">{{ $task['instruction'] }}</p>
                        @if ($task['owner'])
                            <p>{{ __('sponsors.task_owner', ['name' => $task['owner']]) }}</p>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
        @can('updateProduction', $event)
            <livewire:events.components.production :eventId="$event->id" />
        @endcan
    </x-layouts.workspace-canvas>
</x-layouts.app>
