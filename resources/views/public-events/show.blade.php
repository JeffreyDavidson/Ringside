<x-layouts.public-event :$page>
    <main id="event-details" class="mx-auto max-w-6xl px-4 py-8 sm:px-8 sm:py-14">
        @if ($page->isPreview)
            <div class="border-ringside-warning text-ringside-warning mb-6 border p-4 font-bold">
                {{ __('public-events.draft') }} — {{ __('public-events.private') }}
            </div>
        @endif
        <header class="border-ringside-line border-b pb-7">
            <h1 class="font-display max-w-4xl text-4xl leading-tight break-words uppercase sm:text-6xl">
                {{ $page->title }}
            </h1>
            @if ($page->promotion)
                <p class="text-ringside-muted mt-3">{{ __('public-events.by') }} {{ $page->promotion }}</p>
            @endif
        </header>
        @if ($page->scheduleChanged)
            <p class="my-10 text-lg">{{ __('public-events.updating') }}</p>
        @else
            <div class="mt-8 grid min-w-0 gap-10 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                <section aria-label="{{ __('public-events.public_facts') }}" class="min-w-0">
                    @if ($page->announcement !== 'scheduled')
                        <p class="text-ringside-signal text-lg font-bold">
                            {{ \App\Enums\Events\PublicAnnouncement::from($page->announcement)->label() }}
                        </p>
                    @endif
                    @if ($page->notice)
                        <p class="mt-4 whitespace-pre-line">{{ $page->notice }}</p>
                    @endif
                    @if ($page->date)
                        <p class="mt-4 text-xl font-bold">
                            <time datetime="{{ $page->date }}">{{ \Illuminate\Support\Carbon::parse($page->date)->setTimezone($page->timezone)->format('l, F j, Y') }}</time>
                        </p>
                        <p>
                            {{ \Illuminate\Support\Carbon::parse($page->date)->setTimezone($page->timezone)->format('g:i A T (P)') }}
                        </p>
                        <p class="text-ringside-muted text-sm">{{ $page->timezone }}</p>
                    @endif
                    <p class="mt-5 text-lg font-bold">{{ $page->venue ?? __('public-events.venue_tba') }}</p>
                    @if ($page->address)
                        <p class="text-ringside-muted">{{ $page->address }}</p>
                    @endif
                    @if ($page->doors)
                        <p class="mt-4">
                            {{ __('public-events.doors') }}: {{ \Illuminate\Support\Carbon::parse($page->doors)->setTimezone($page->timezone)->format('M j, g:i A T (P)') }}
                        </p>
                    @endif
                    @if ($page->summary)
                        <p class="mt-6 max-w-prose whitespace-pre-line">{{ $page->summary }}</p>
                    @endif
                    @if ($page->tickets)
                        <a
                            href="{{ $page->tickets }}"
                            rel="noopener noreferrer"
                            class="bg-ringside-red hover:bg-ringside-red-hover active:bg-ringside-red-dark mt-6 inline-flex min-h-14 items-center px-7 font-bold focus-visible:outline-2 focus-visible:outline-offset-4"
                        >{{ __('public-events.tickets') }}</a>
                    @endif
                    @if ($page->tickets)
                        <p class="text-ringside-muted mt-2 text-sm">
                            {{ __('public-events.ticket_provider', ['host'=>parse_url($page->tickets, PHP_URL_HOST)]) }}
                        </p>
                    @endif
                    @if ($page->posterUrl)
                        <figure class="mt-8">
                            <a
                                href="{{ $page->posterUrl }}"
                                aria-label="{{ $page->posterAlt }}"
                                class="block focus-visible:outline-2 focus-visible:outline-offset-4"
                            >
                                <img
                                    src="{{ $page->posterUrl }}"
                                    alt="{{ $page->posterAlt }}"
                                    class="h-auto w-full object-contain"
                                />
                            </a>
                            @if ($page->posterCaption)
                                <figcaption class="text-ringside-muted mt-3 text-sm">
                                    {{ $page->posterCaption }}
                                </figcaption>
                            @endif
                        </figure>
                    @endif
                </section>
                @if (! \App\Enums\Events\PublicAnnouncement::from($page->announcement)->isNotice())
                    <section class="min-w-0">
                        <h2 class="font-display text-3xl uppercase">
                            {{ $page->isResults ? __('public-events.results') : __('public-events.card') }}
                        </h2>
                        @if ($page->resultsChanged)
                            <p class="mt-5">{{ __('public-events.results_updating') }}</p>
                        @else
                            @forelse ($page->rows as $row)
                                <article class="border-ringside-line border-b py-6">
                                    <h3 class="text-xl leading-snug font-bold break-words">{{ $row['label'] }}</h3>
                                    @if ($row['details'])
                                        <p class="text-ringside-muted mt-2">{{ $row['details'] }}</p>
                                    @endif
                                    @if ($page->isResults && $row['outcome'])
                                        <p class="mt-3 font-bold">{{ $row['outcome'] }}</p>
                                    @endif
                                </article>
                            @empty
                                <p class="text-ringside-muted mt-5">{{ __('public-events.none') }}</p>
                            @endforelse
                        @endif
                        @if ($page->correctedAt)
                            <p class="text-ringside-muted mt-4 text-sm">
                                {{ __('public-events.corrected', ['date'=>\Illuminate\Support\Carbon::parse($page->correctedAt)->setTimezone($page->timezone)->format('M j, Y')]) }}
                            </p>
                        @endif
                        @if ($page->isResults)
                            <a
                                href="{{ $page->cardUrl }}"
                                class="text-ringside-signal-soft mt-6 inline-flex min-h-11 items-center underline underline-offset-4"
                            >{{ __('public-events.back') }}</a>
                        @elseif ($page->resultsUrl)
                            <a
                                href="{{ $page->resultsUrl }}"
                                class="text-ringside-signal-soft mt-6 inline-flex min-h-11 items-center underline underline-offset-4"
                            >{{ __('public-events.results_link') }}</a>
                        @endif
                    </section>
                @endif
            </div>
        @endif
        <footer class="border-ringside-line text-ringside-muted mt-12 border-t pt-6 text-sm">
            <a
                href="{{ route('home') }}"
                class="inline-flex min-h-11 items-center underline underline-offset-4"
            >{{ __('public-events.powered') }}</a>
        </footer>
    </main>
</x-layouts.public-event>
