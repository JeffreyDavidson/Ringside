@props(['sidebar', 'title' => null])

<x-layouts.app>
    <x-layouts.workspace-canvas class="flex flex-col gap-5 p-4 lg:gap-7.5 lg:p-7">
        @if ($title)
            <div>
                <h1 class="font-display text-ringside-ink text-2xl leading-none tracking-tight uppercase">
                    {{ $title }}
                </h1>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-7.5">
            <div class="col-span-1 min-w-0">
                <div class="grid gap-5 lg:gap-7.5">{{ $sidebar }}</div>
            </div>
            <div class="col-span-1 min-w-0 lg:col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">{{ $slot }}</div>
            </div>
        </div>
    </x-layouts.workspace-canvas>
</x-layouts.app>
