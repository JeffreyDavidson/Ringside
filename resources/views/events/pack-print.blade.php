<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $event->name }} · {{ __('event-pack.title') }}</title>
    @vite('resources/js/event-pack-print.js')
</head>
<body class="event-pack-print">
    <nav class="pack-print-controls" aria-label="{{ __('event-pack.print_options') }}">
        <a href="{{ route('events.pack.show', $event) }}">&larr; {{ __('event-pack.back_to_pack') }}</a>
        @can('viewProduction', $event)
            <a href="{{ route('events.production-sheet.show', ['event' => $event, 'print' => 1]) }}">{{ __('production.print') }}</a>
        @endcan
        <fieldset>
            <legend>{{ __('event-pack.sections') }}</legend>
            @foreach (['running_order', 'roster', 'notes', ...(($content['sponsor_tasks'] ?? []) === [] ? [] : ['sponsor_setup'])] as $section)
                <label
                    ><input type="checkbox" data-print-section="{{ $section }}" checked />
                    {{ __('event-pack.'.$section) }}</label>
            @endforeach
        </fieldset>
        <button type="button" data-print>{{ __('event-pack.print') }}</button>
        <p>{{ __('event-pack.print_help') }}</p>
        @if ($needsReview || $unplaced !== [])
            <p class="pack-print-warning">{{ __('event-pack.review_notice') }}</p>
        @endif
        @if ($content['timezone'] !== $card['timezone'])
            <p>{{ __('event-pack.zone_notice', ['saved' => $content['timezone'], 'current' => $card['timezone']]) }}</p>
        @endif
    </nav>
    <main>
        <section data-section="running_order">
            <table class="pack-running">
                <colgroup>
                    <col style="width: 24%" />
                    <col style="width: 49%" />
                    <col style="width: 27%" />
                </colgroup>
                <thead>
                    <tr>
                        <td colspan="3">
                            <x-events.pack.print-heading
                                :$event
                                :$content
                                :$pack
                                :section="__('event-pack.running_order')"
                            />
                            @if ($needsReview || $unplaced !== [])
                                <p class="pack-print-warning">{{ __('event-pack.review_notice') }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('event-pack.schedule') }}</th>
                        <th>{{ __('event-pack.match_segment') }}</th>
                        <th>{{ __('event-pack.on_the_night') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($running as $row)
                        <tr>
                            <td>
                                <strong>{{ str_replace('T', ' ', $row['timing']['planned_at'] ?? '—') }}</strong
                                ><br />{{ __('event-pack.revised_short') }}: {{ str_replace('T', ' ', $row['timing']['revised_at'] ?? '—') }}<br />{{ __('event-pack.minutes') }}: {{ $row['timing']['minutes'] ?? '—' }}
                            </td>
                            <td>
                                <strong>{{ $row['label'] }}</strong>
                                <p>{{ $row['details'] }}</p>
                                @if ($row['referees'])
                                    <p>{{ __('event-pack.referee') }}: {{ $row['referees'] }}</p>
                                @endif
                                @if ($row['warning'])
                                    <p class="pack-print-warning">{{ __('event-pack.unavailable') }}</p>
                                @endif
                                <p class="pack-preserve">{{ $row['timing']['notes'] ?? '' }}</p>
                            </td>
                            <td>
                                {{ __('event-pack.actual_start') }}
                                <div class="pack-write-line"></div>
                                {{ __('event-pack.actual_finish') }}
                                <div class="pack-write-line"></div>
                                {{ __('event-pack.changes') }}
                                <div class="pack-write-line"></div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('event-pack.empty_card') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($unplaced !== [])
                <p>{{ __('event-pack.unplaced') }}: {{ implode(', ', array_column($unplaced, 'label')) }}</p>
            @endif
        </section>
        <section data-section="roster">
            <table class="pack-roster">
                <colgroup>
                    <col style="width: 45%" />
                    <col style="width: 25%" />
                    <col style="width: 30%" />
                </colgroup>
                <thead>
                    <tr>
                        <td colspan="3">
                            <x-events.pack.print-heading :$event :$content :$pack :section="__('event-pack.roster')" />
                            <p>{{ __('event-pack.membership_note') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('event-pack.person_role') }}</th>
                        <th>{{ __('event-pack.assignments') }}</th>
                        <th>{{ __('event-pack.check_in') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($card['roster'] as $person)
                        <tr>
                            <td>
                                <strong>{{ $person['name'] }}</strong><br />{{ $person['role'] }}
                                @if ($person['warning'])
                                    <p class="pack-print-warning">{{ __('event-pack.unavailable') }}</p>
                                @endif
                            </td>
                            <td>{{ __('event-pack.matches') }} {{ implode(', ', $person['matches']) }}</td>
                            <td>
                                <span class="pack-checkbox"></span> {{ __('event-pack.arrived') }}<br /><span
                                    class="pack-checkbox"
                                ></span>
                                {{ __('event-pack.briefed') }}
                            </td>
                        </tr>
                    @endforeach
                    @for ($i = 0; $i < 4; $i++)
                        <tr>
                            <td>
                                {{ __('event-pack.extra_person') }}
                                <div class="pack-write-line"></div>
                            </td>
                            <td><div class="pack-write-line"></div></td>
                            <td>
                                <span class="pack-checkbox"></span> {{ __('event-pack.arrived') }}<br /><span
                                    class="pack-checkbox"
                                ></span>
                                {{ __('event-pack.briefed') }}
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </section>
        <section data-section="notes">
            <table>
                <thead>
                    <tr>
                        <td>
                            <x-events.pack.print-heading :$event :$content :$pack :section="__('event-pack.notes')" />
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <h2>{{ __('event-pack.general_notes') }}</h2>
                            <div class="pack-preserve">{{ $content['notes'] }}</div>
                            @for ($i = 0; $i < 5; $i++)
                                <div class="pack-write-line"></div>
                            @endfor
                            <h2>{{ __('event-pack.follow_up') }}</h2>
                            <div class="pack-preserve">{{ $content['follow_up'] }}</div>
                            @for ($i = 0; $i < 5; $i++)
                                <div class="pack-write-line"></div>
                            @endfor
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
        @if (($content['sponsor_tasks'] ?? []) !== [])
            <section data-section="sponsor_setup">
                <table>
                    <thead>
                        <tr>
                            <td>
                                <x-events.pack.print-heading
                                    :$event
                                    :$content
                                    :$pack
                                    :section="__('sponsors.setup_title')"
                                />
                                <p class="pack-print-meta">{{ __('sponsors.plan_notice') }}</p>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($content['sponsor_tasks'] as $task)
                            <tr>
                                <td>
                                    <strong>{{ $task['label'] }}</strong>
                                    <p class="pack-preserve">{{ $task['instruction'] }}</p>
                                    @if ($task['owner'])
                                        <p>{{ __('sponsors.task_owner', ['name' => $task['owner']]) }}</p>
                                    @endif
                                    <p><span class="pack-checkbox"></span> {{ __('sponsors.completed') }}</p>
                                    <div class="pack-write-line"></div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </main>
</body>
</html>
