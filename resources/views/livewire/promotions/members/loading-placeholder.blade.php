<section
    class="border-border bg-surface-panel min-w-0 border"
    role="status"
    aria-busy="true"
    data-test="promotion-members-loading-placeholder"
    aria-labelledby="promotion-members-loading-title"
>
    <span class="sr-only">{{ __('core.loading_table') }}</span>

    <div class="motion-safe:animate-pulse" aria-hidden="true">
        <header class="border-border flex flex-wrap items-end justify-between gap-4 border-b px-5 py-4 lg:px-6">
            <div class="flex flex-col gap-3">
                <div id="promotion-members-loading-title" class="bg-border/60 h-6 w-44 max-w-full"></div>
                <div class="bg-border/50 h-3 w-28"></div>
            </div>
            <div class="bg-border/50 h-4 w-32"></div>
        </header>

        <div class="border-border grid gap-4 border-b px-5 py-5 lg:grid-cols-[minmax(0,1fr)_12rem] lg:px-6">
            <div class="border-border bg-surface h-14 border"></div>
            <div class="border-border bg-surface h-14 border"></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[38rem] text-left text-sm" aria-hidden="true">
                <thead>
                    <tr class="border-border border-b">
                        @foreach ([36, 22, 24, 18] as $width)
                            <th class="px-5 py-3 text-start lg:px-6">
                                <div class="bg-border/60 h-3" style="width: {{ $width }}%"></div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, 3) as $row)
                        <tr class="border-border border-b last:border-b-0">
                            @foreach ([42, 24, 24, 18] as $width)
                                <td class="px-5 py-4 lg:px-6">
                                    <div class="bg-border/50 h-3" style="width: {{ $width }}%"></div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
