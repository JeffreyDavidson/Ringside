<div class="flex min-w-0 flex-col gap-6" role="status" aria-busy="true" data-test="table-loading-placeholder">
    <span class="sr-only">{{ __('core.loading_table') }}</span>

    <div class="motion-safe:animate-pulse" aria-hidden="true">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="bg-ringside-line/60 h-8 w-48 max-w-full"></div>
            <div class="bg-ringside-line/60 h-11 w-32 max-w-full"></div>
        </div>

        <section class="border-ringside-line bg-ringside-surface-header min-w-0 border">
            <div class="border-ringside-line flex min-h-12 items-center gap-3 border-b px-4 py-3">
                <div class="bg-ringside-line/60 h-3 w-12"></div>
                <div class="bg-ringside-line/60 h-3 w-16"></div>
                <div class="bg-ringside-line/60 h-3 w-14"></div>
            </div>

            <div class="border-ringside-line flex flex-wrap items-center justify-between gap-3 border-b p-4">
                <div class="border-ringside-line bg-ringside-surface h-11 w-full border sm:max-w-xs"></div>
                <div class="flex flex-wrap gap-3">
                    <div class="border-ringside-line bg-ringside-surface h-11 w-24 border"></div>
                    <div class="border-ringside-line bg-ringside-surface h-11 w-24 border"></div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] border-collapse" aria-hidden="true">
                    <thead>
                        <tr class="border-ringside-line border-b">
                            @foreach ([24, 16, 20, 12] as $width)
                                <th class="px-4 py-3 text-start">
                                    <div class="bg-ringside-line/60 h-3" style="width: {{ $width }}%"></div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (range(1, 5) as $row)
                            <tr class="border-ringside-line border-b last:border-b-0">
                                @foreach ([32, 22, 26, 14] as $width)
                                    <td class="px-4 py-4">
                                        <div class="bg-ringside-line/50 h-3" style="width: {{ $width }}%"></div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-ringside-line flex min-h-16 flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
                <div class="bg-ringside-line/60 h-3 w-28"></div>
                <div class="bg-ringside-line/60 h-9 w-36"></div>
            </div>
        </section>
    </div>
</div>
