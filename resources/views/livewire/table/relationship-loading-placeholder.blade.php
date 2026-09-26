<div
    class="border-ringside-line bg-ringside-surface-header min-w-0 border"
    role="status"
    aria-busy="true"
    data-test="relationship-table-loading-placeholder"
>
    <span class="sr-only">{{ __('core.loading_table') }}</span>

    <div class="motion-safe:animate-pulse" aria-hidden="true">
        <div class="border-ringside-line flex min-h-12 items-center justify-between gap-4 border-b px-4 py-3">
            <div class="bg-ringside-line/60 h-3 w-32 max-w-full"></div>
            <div class="border-ringside-line bg-ringside-surface h-8 w-36 max-w-full border"></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[32rem] border-collapse" aria-hidden="true">
                <thead>
                    <tr class="border-ringside-line border-b">
                        @foreach ([24, 18, 20, 14] as $width)
                            <th class="px-4 py-3 text-start">
                                <div class="bg-ringside-line/60 h-3" style="width: {{ $width }}%"></div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, 3) as $row)
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
    </div>
</div>
