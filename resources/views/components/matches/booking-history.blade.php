<section
    x-data="bookingHistory"
    data-test="booking-history"
    data-failure-message="{{ __('matches.history.failed') }}"
    data-missing-result="{{ __('matches.history.result_missing') }}"
    data-incomplete-result="{{ __('matches.history.result_incomplete') }}"
    data-by-label="{{ __('matches.history.by') }}"
    data-deleted-label="{{ __('matches.history.deleted') }}"
    class="border-ringside-outline space-y-3 border-t pt-4"
    aria-labelledby="booking-history-heading"
    :aria-busy="busy"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 id="booking-history-heading" class="text-ringside-ink text-sm font-semibold">
            {{ __('matches.history.title') }}
        </h3>
        <x-buttons.light
            type="button"
            x-on:click="check()"
            x-bind:disabled="busy"
            class="min-h-11"
            data-test="check-history"
        >
            <span x-show="! busy">{{ __('matches.history.check') }}</span>
            <span x-show="busy" x-cloak>{{ __('matches.history.checking') }}</span>
        </x-buttons.light>
    </div>
    <p class="text-ringside-muted text-sm">{{ __('matches.history.hint') }}</p>
    <div
        x-show="error"
        x-cloak
        role="status"
        class="border-ringside-error bg-ringside-error-surface text-ringside-error flex items-start gap-2 border px-3 py-3 text-sm"
    >
        <x-heroicon-o-exclamation-circle class="size-5 shrink-0" aria-hidden="true" />
        <span x-text="error"></span>
    </div>
    <template x-if="snapshot">
        <div class="space-y-4" aria-live="polite">
            <p x-show="! snapshot.available" class="text-ringside-muted text-sm">
                {{ __('matches.history.unavailable') }}
            </p>
            <template x-if="snapshot.available">
                <div class="space-y-4">
                    <p class="text-ringside-muted text-sm">{{ __('matches.history.checked') }}</p>
                    <template x-if="snapshot.direct">
                        <div class="border-ringside-outline space-y-2 border-b pb-4">
                            <h4 class="text-sm font-semibold">{{ __('matches.history.direct') }}</h4>
                            <p class="text-ringside-muted text-sm">{{ __('matches.history.direct_scope') }}</p>
                            <p
                                x-show="snapshot.direct.recorded === 0 && snapshot.direct.incomplete === 0"
                                class="text-sm"
                            >
                                {{ __('matches.history.direct_empty') }}
                            </p>
                            <dl
                                x-show="snapshot.direct.recorded > 0 || snapshot.direct.incomplete > 0"
                                class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2"
                            >
                                <div>
                                    <dt x-text="snapshot.direct.first"></dt>
                                    <dd x-text="snapshot.direct.firstWins" class="font-semibold tabular-nums"></dd>
                                </div>
                                <div>
                                    <dt x-text="snapshot.direct.second"></dt>
                                    <dd x-text="snapshot.direct.secondWins" class="font-semibold tabular-nums"></dd>
                                </div>
                                <div>
                                    <dt>{{ __('matches.history.draws') }}</dt>
                                    <dd x-text="snapshot.direct.draws" class="tabular-nums"></dd>
                                </div>
                                <div x-show="snapshot.direct.neutral > 0">
                                    <dt>{{ __('matches.history.neutral') }}</dt>
                                    <dd x-text="snapshot.direct.neutral" class="tabular-nums"></dd>
                                </div>
                                <div x-show="snapshot.direct.incomplete > 0">
                                    <dt>{{ __('matches.history.incomplete') }}</dt>
                                    <dd x-text="snapshot.direct.incomplete" class="tabular-nums"></dd>
                                </div>
                            </dl>
                            <a
                                x-show="snapshot.direct.url"
                                x-bind:href="snapshot.direct.url"
                                target="_blank"
                                rel="noopener"
                                class="text-ringside-signal-soft inline-flex min-h-11 items-center text-sm underline"
                                >{{ __('matches.history.sources') }}<span class="sr-only">
                                    ({{ __('matches.history.new_tab') }})</span
                                ></a>
                        </div>
                    </template>
                    <div class="space-y-2">
                        <h4 class="flex items-center gap-2 text-sm font-semibold">
                            <x-heroicon-o-information-circle
                                class="text-ringside-warning size-5 shrink-0"
                                aria-hidden="true"
                            />{{ __('matches.history.recent') }}
                        </h4>
                        <p class="text-ringside-muted text-sm">{{ __('matches.history.window') }}</p>
                        <p x-show="snapshot.totalNotices === 0" class="text-sm">
                            {{ __('matches.history.no_repeats') }}
                        </p>
                        <template x-for="(notice, index) in snapshot.notices" :key="index">
                            <div class="border-ringside-outline space-y-1 border-b py-2 text-sm">
                                <p class="font-medium" x-text="`${notice.first} vs ${notice.second}`"></p>
                                <p>
                                    <span>{{ __('matches.history.recorded') }}: </span
                                    ><span x-text="notice.recorded"></span> ·
                                    <span>{{ __('matches.history.incomplete') }}: </span
                                    ><span x-text="notice.incomplete"></span>
                                </p>
                                <p>
                                    <span>{{ __('matches.history.latest_pairing') }}: </span
                                    ><span x-text="notice.latestDate"></span>
                                </p>
                                <p class="text-ringside-muted" x-text="`${notice.type} · ${notice.finish}`"></p>
                                <a
                                    x-bind:href="notice.sourceUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-ringside-signal-soft inline-flex min-h-11 items-center underline"
                                >
                                    <span x-text="`${notice.event} · #${notice.number}`"></span>
                                    <span class="sr-only"> ({{ __('matches.history.new_tab') }})</span>
                                </a>
                                <a
                                    x-show="notice.url"
                                    x-bind:href="notice.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-ringside-signal-soft inline-flex min-h-11 items-center underline"
                                    >{{ __('matches.history.sources') }}<span class="sr-only">
                                        ({{ __('matches.history.new_tab') }})</span
                                    ></a>
                            </div>
                        </template>
                        <div x-show="snapshot.pages > 1" class="flex flex-wrap items-center gap-3">
                            <span
                                class="text-ringside-muted text-sm tabular-nums"
                                x-text="`${snapshot.page} / ${snapshot.pages} · ${snapshot.totalNotices}`"
                            ></span>
                            <x-buttons.light
                                type="button"
                                x-on:click="check(snapshot.page - 1)"
                                x-bind:disabled="busy || snapshot.page <= 1"
                                class="min-h-11"
                            >
                                {{ __('matches.history.previous') }}</x-buttons.light>
                            <x-buttons.light
                                type="button"
                                x-on:click="check(snapshot.page + 1)"
                                x-bind:disabled="busy || snapshot.page >= snapshot.pages"
                                class="min-h-11"
                            >
                                {{ __('matches.history.next') }}</x-buttons.light>
                        </div>
                    </div>
                    <template x-for="subject in snapshot.subjects" :key="subject.key">
                        <details class="border-ringside-outline border-t pt-3">
                            <summary class="min-h-11 cursor-pointer text-sm font-semibold">
                                <span x-text="subject.name + (subject.deleted ? ` (${messages.deletedLabel})` : '')"></span>
                                <span class="text-ringside-muted block font-normal">
                                    {{ __('matches.history.latest') }}:
                                    <span x-text="subject.latestRecordedDate ?? @js(__('matches.history.no_recorded'))"></span>
                                </span>
                            </summary>
                            <div class="space-y-3 py-2 text-sm">
                                <p x-show="subject.recent.length === 0">{{ __('matches.history.empty') }}</p>
                                <template x-for="(row, index) in subject.recent" :key="index">
                                    <div class="border-ringside-outline space-y-1 border-b py-2">
                                        <a
                                            x-bind:href="row.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-ringside-signal-soft inline-flex min-h-11 items-center underline"
                                            x-text="`${row.event} · ${row.date} · #${row.number}`"
                                        ></a>
                                        <p x-text="sides(row)"></p>
                                        <p class="text-ringside-muted" x-text="row.type"></p>
                                        <p x-text="result(row)"></p>
                                    </div>
                                </template>
                                <template x-if="subject.url"
                                    ><a
                                        x-bind:href="subject.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-ringside-signal-soft inline-flex min-h-11 items-center underline"
                                        >{{ __('matches.history.all') }}<span class="sr-only">
                                            ({{ __('matches.history.new_tab') }})</span
                                        ></a
                                    ></template>
                            </div>
                        </details>
                    </template>
                </div>
            </template>
        </div>
    </template>
</section>
