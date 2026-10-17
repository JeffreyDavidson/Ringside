<x-layouts.app :title="__('public-events.editor_title')">
    <div class="mx-auto max-w-5xl min-w-0 px-4 py-7 sm:px-7">
        <a
            href="{{ route('events.show', $event) }}"
            class="text-signal-soft inline-flex min-h-11 items-center underline"
        >{{ $event->name }}</a>
        <h1 class="font-display mt-3 text-3xl uppercase">{{ __('public-events.editor_title') }}</h1>
        <p class="text-muted-foreground mt-3 max-w-prose">{{ __('public-events.private_help') }}</p>
        <div class="border-border mt-6 border-y py-4">
            <p class="font-bold">
                {{ $publication?->published_at ? __('public-events.published') : __('public-events.private') }} · {{ __('public-events.draft') }} {{ $publication?->version ?? 0 }}
            </p>
            @if ($publication?->published_at)
                <p class="text-muted-foreground mt-2">
                    {{ __('public-events.publisher', ['name'=>$publication->publisher?->full_name ?? __('public-events.former_publisher')]) }}
                </p>
                @if ($publication->results_published_at)
                    <p class="text-muted-foreground mt-2">
                        {{ __('public-events.results_publisher', ['name'=>$publication->resultsPublisher?->full_name ?? __('public-events.former_publisher')]) }}
                    </p>
                @endif
                <p class="text-muted-foreground mt-2">
                    {{ __('public-events.last_published', ['date'=>$publication->published_at->format('M j, Y g:i A T')]) }}
                </p>
                <a
                    href="{{ route('shows.show', ['publicId'=>$publication->public_id,'slug'=>$publication->slug]) }}"
                    class="text-signal-soft inline-flex min-h-11 items-center underline"
                >{{ __('public-events.open_public') }}</a>
            @endif
            @if ($needsReview)
                <p class="text-warning mt-2">{{ __('public-events.source_changed') }}</p>
            @endif
        </div>
        @session('success')
            <p role="status" class="text-success mt-5">{{ $value }}</p>
        @endsession
        @if ($errors->any())
            <div role="alert" class="border-error bg-error-surface text-error mt-5 border p-4">
                <p class="flex items-center gap-2 font-bold">
                    <x-heroicon-o-exclamation-circle class="h-5 w-5" />{{ __('public-events.errors') }}
                </p>
                <ul class="mt-2 list-disc pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form
            method="POST"
            action="{{ route('events.public-pages.update', $event) }}"
            enctype="multipart/form-data"
            class="mt-7 space-y-8"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="version" value="{{ $publication?->version ?? 0 }}" />
            <input type="hidden" name="fingerprint" value="{{ $source['fingerprint'] }}" />
            <section class="space-y-4">
                <h2 class="font-display text-2xl uppercase">{{ __('public-events.public_facts') }}</h2>
                <p>
                    {{ $source['date'] ? \Illuminate\Support\Carbon::parse($source['date'])->setTimezone($source['timezone'])->format('M j, Y g:i A T (P)') : __('public-events.date_tba') }} · {{ $source['venue'] ?? __('public-events.venue_tba') }} · {{ $source['timezone'] }}
                </p>
                <label class="block"
                    >{{ __('public-events.announcement') }}<select
                        name="announcement"
                        class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                    >
                        @foreach (\App\Enums\Events\PublicAnnouncement::cases() as $state)
                            <option
                                value="{{ $state->value }}"
                                @selected(old('announcement', $draft['announcement']) === $state->value)
                            >
                                {{ $state->label() }}
                            </option>
                        @endforeach</select
                ></label>
                <label class="block"
                    >{{ __('public-events.summary') }}<textarea
                        name="summary"
                        maxlength="2000"
                        rows="3"
                        class="border-border bg-surface mt-2 w-full border p-3"
                        >{{ old('summary', $draft['summary']) }}</textarea>
                </label>
                <label class="block"
                    >{{ __('public-events.notice') }}<textarea
                        name="notice"
                        maxlength="1000"
                        rows="2"
                        class="border-border bg-surface mt-2 w-full border p-3"
                        >{{ old('notice', $draft['notice']) }}</textarea>
                </label>
                <p class="text-muted-foreground text-sm">{{ __('public-events.notice_help') }}</p>
                <label class="flex min-h-11 items-center gap-3"
                    ><input
                        type="checkbox"
                        name="address"
                        value="1"
                        @checked(old('address', $draft['address']))
                    />{{ __('public-events.include_address') }}</label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label
                        >{{ __('public-events.doors') }}<input
                            type="datetime-local"
                            name="doors_at"
                            value="{{ old('doors_at', $draft['doors_at']) }}"
                            class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                    /></label>
                    <label
                        >{{ __('public-events.doors_offset') }}<input
                            type="text"
                            name="doors_offset"
                            placeholder="-04:00"
                            value="{{ old('doors_offset', $draft['doors_offset']) }}"
                            class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                    /></label>
                </div>
                <p class="text-muted-foreground text-sm">{{ __('public-events.doors_help') }}</p>
                <label class="block"
                    >{{ __('public-events.ticket_url') }}<input
                        type="url"
                        name="tickets"
                        value="{{ old('tickets', $draft['tickets']) }}"
                        class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                /></label>
            </section>
            <section class="border-border space-y-4 border-t pt-6">
                <h2 class="font-display text-2xl uppercase">{{ __('public-events.poster') }}</h2>
                <p class="text-muted-foreground">{{ __('public-events.poster_help') }}</p>
                <label class="block"
                    >{{ __('public-events.poster') }}<input
                        type="file"
                        name="poster"
                        accept="image/jpeg,image/png,image/webp"
                        class="mt-2 block min-h-11 max-w-full"
                /></label>
                <label class="block"
                    >{{ __('public-events.poster_alt') }}<input
                        type="text"
                        name="poster_alt"
                        maxlength="400"
                        value="{{ old('poster_alt', $draft['poster']['alt'] ?? '') }}"
                        class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                /></label>
                <label class="block"
                    >{{ __('public-events.poster_caption') }}<input
                        type="text"
                        name="poster_caption"
                        maxlength="1000"
                        value="{{ old('poster_caption', $draft['poster']['caption'] ?? '') }}"
                        class="border-border bg-surface mt-2 block min-h-11 w-full border p-3"
                /></label>
                @if ($draft['poster'])
                    <label class="flex min-h-11 items-center gap-3"
                        ><input
                            type="checkbox"
                            name="remove_poster"
                            value="1"
                        />{{ __('public-events.remove_poster') }}</label>
                @endif
            </section>
            <section class="border-border border-t pt-6">
                <h2 class="font-display text-2xl uppercase">{{ __('public-events.card') }}</h2>
                <p class="text-muted-foreground mt-3">{{ __('public-events.results_help') }}</p>
                @forelse ($source['matches'] as $index => $match)
                    @php($choice = collect($draft['matches'])->firstWhere('id', $match['id']))
                    <fieldset class="border-border mt-5 min-w-0 border-b pb-5">
                        <legend class="text-lg font-bold break-words">{{ $match['label'] }}</legend>
                        <input type="hidden" name="matches[{{ $index }}][id]" value="{{ $match['id'] }}" />
                        <label class="flex min-h-11 items-center gap-3"
                            ><input
                                type="checkbox"
                                name="matches[{{ $index }}][selected]"
                                value="1"
                                @checked(old('matches.'.$index.'.selected', $choice !== null))
                            />{{ __('public-events.select_match') }}</label>
                        <div class="flex flex-wrap gap-x-5">
                            @foreach (['type' => 'include_type', 'stipulation' => 'include_stipulation', 'titles' => 'include_titles'] as $field => $label)
                                <label class="flex min-h-11 items-center gap-3"
                                    ><input
                                        type="checkbox"
                                        name="matches[{{ $index }}][{{ $field }}]"
                                        value="1"
                                        @checked(old('matches.'.$index.'.'.$field, $choice[$field] ?? false))
                                    />{{ __('public-events.'.$label) }}</label>
                            @endforeach
                        </div>
                        @if ($match['finish'])
                            <label class="flex min-h-11 items-center gap-3"
                                ><input
                                    type="checkbox"
                                    name="results[]"
                                    value="{{ $match['id'] }}"
                                    @checked(in_array($match['id'], old('results', $draft['results'])))
                                />{{ __('public-events.select_result') }}: {{ $match['winner'] }} {{ $match['finish'] }}</label>
                        @else
                            <p class="text-muted-foreground mt-2">{{ __('public-events.unrecorded') }}</p>
                        @endif
                    </fieldset>
                @empty
                    <p class="text-muted-foreground mt-4">{{ __('public-events.none') }}</p>
                @endforelse
            </section>
            <x-buttons.primary type="submit">{{ __('public-events.save') }}</x-buttons.primary>
            <input type="hidden" name="complete" value="1" />
        </form>
        @if ($publication)
            <section class="border-border mt-9 border-t pt-7">
                <h2 class="font-display text-2xl uppercase">{{ __('public-events.release_title') }}</h2>
                <p class="text-muted-foreground mt-3">{{ __('public-events.published_help') }}</p>
                <a
                    href="{{ route('events.public-pages.show', $event) }}"
                    class="text-signal-soft inline-flex min-h-11 items-center underline"
                >{{ __('public-events.preview') }}</a>
                <form method="POST" action="{{ route('events.public-pages.publish', $event) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="version" value="{{ $publication->version }}" />
                    <input type="hidden" name="fingerprint" value="{{ $source['fingerprint'] }}" />
                    <label class="flex min-h-11 items-start gap-3"
                        ><input
                            type="checkbox"
                            name="reviewed"
                            value="1"
                            required
                            class="mt-1.5"
                        />{{ __('public-events.reviewed') }}</label>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            name="section"
                            value="card"
                            class="bg-primary hover:bg-primary-hover min-h-11 px-5 py-3 font-bold"
                        >
                            {{ __('public-events.publish_card') }}</button
                        ><button
                            type="submit"
                            name="section"
                            value="results"
                            class="border-border min-h-11 border px-5 py-3 font-bold"
                        >
                            {{ __('public-events.publish_results') }}
                        </button>
                    </div>
                </form>
                <form
                    method="POST"
                    action="{{ route('events.public-pages.unpublish', $event) }}"
                    class="mt-6 flex flex-wrap gap-3"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="version" value="{{ $publication->version }}" /><input
                        type="hidden"
                        name="fingerprint"
                        value="{{ $source['fingerprint'] }}"
                    />
                    <button
                        type="submit"
                        name="section"
                        value="results"
                        class="text-signal-soft border-border min-h-11 border px-5 py-3 font-bold"
                    >
                        {{ __('public-events.unpublish_results') }}
                    </button>
                    <button
                        type="submit"
                        name="section"
                        value="card"
                        class="text-signal-soft border-border min-h-11 border px-5 py-3 font-bold"
                    >
                        {{ __('public-events.unpublish_card') }}
                    </button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
