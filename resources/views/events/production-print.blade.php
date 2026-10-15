<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $sheet['eventName'] }} · {{ __('production.cue_sheet') }}</title>
    @vite('resources/js/event-pack-print.js')
</head>
<body class="event-pack-print">
    <nav class="pack-print-controls">
        <a href="{{ route('events.production-sheet.show', ['event' => $sheet['event'], 'source' => $sheet['source'], 'person' => $sheet['person'], 'role' => $sheet['role']]) }}">{{ __('production.back_sheet') }}</a>
        <fieldset>
            <legend>{{ __('event-pack.sections') }}</legend>
            @foreach (['crew', 'cues'] as $section)
                <label
                    ><input
                        type="checkbox"
                        data-print-section="{{ $section }}"
                        checked
                    />{{ __('production.sections.'.$section) }}</label>
            @endforeach
        </fieldset>
        <button type="button" data-print>{{ __('event-pack.print') }}</button>
        <p>{{ __('production.print_help') }}</p>
    </nav>
    <main>
        <section data-section="crew">
            <table>
                <colgroup>
                    <col style="width: 30%" />
                    <col style="width: 45%" />
                    <col style="width: 25%" />
                </colgroup>
                <thead>
                    <tr>
                        <td colspan="3">
                            <x-events.production.print-heading :$sheet :section="__('production.call_sheet')" />
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('event-pack.person_role') }}</th>
                        <th>{{ __('production.responsibilities') }}</th>
                        <th>{{ __('event-pack.check_in') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sheet['assignments'] as $assignment)
                        <tr>
                            <td>
                                <strong>{{ $assignment['name'] }}</strong>
                                <p>
                                    {{ \App\Enums\Crew\CrewRole::from($assignment['role'])->label() }} · {{ $assignment['station'] }}
                                </p>
                                <p>
                                    {{ __('production.call_time') }}: {{ str_replace('T', ' ', $assignment['call_at'] ?? __('production.not_set')) }}<br />{{ $assignment['timezone'] }} {{ $assignment['offset'] }}
                                </p>
                            </td>
                            <td>
                                @foreach (['setup', 'during', 'strike'] as $phase)
                                    <p>
                                        <span class="pack-checkbox"></span>
                                        {{ __('production.'.$phase) }}:
                                        <span class="pack-preserve">{{ $assignment[$phase] ?? '' }}</span>
                                    </p>
                                @endforeach
                            </td>
                            <td>
                                <p><span class="pack-checkbox"></span>{{ __('event-pack.arrived') }}</p>
                                <p><span class="pack-checkbox"></span>{{ __('event-pack.briefed') }}</p>
                                <div class="pack-write-line"></div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('production.empty_assignments') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section data-section="cues">
            <table>
                <colgroup>
                    <col style="width: 20%" />
                    <col style="width: 60%" />
                    <col style="width: 20%" />
                </colgroup>
                <thead>
                    <tr>
                        <td colspan="3">
                            <x-events.production.print-heading :$sheet :section="__('production.cue_sheet')" />
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('event-pack.schedule') }}</th>
                        <th>{{ __('production.cue_sheet') }}</th>
                        <th>{{ __('event-pack.changes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet['rows'] as $row)
                        <tr>
                            <td>
                                {{ str_replace('T', ' ', $row['timing']['revised_at'] ?? $row['timing']['planned_at'] ?? __('production.not_set')) }}
                            </td>
                            <td>
                                <strong>{{ $row['label'] }}</strong
                                ><x-events.production.cues
                                    :cues="$row['cues']"
                                    :assignments="$sheet['allAssignments']"
                                />
                                @if (! $row['cues'])
                                    <p>{{ __('production.no_cues') }}</p>
                                @endif
                            </td>
                            <td>
                                <div class="pack-write-line"></div>
                                <div class="pack-write-line"></div>
                                <div class="pack-write-line"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
