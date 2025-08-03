@props(['sidebar', 'title' => null])

<x-layouts.app>
    <x-container-fluid>
        @if($title)
            <div class="mb-5 lg:mb-7.5">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
            </div>
        @endif
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    {{ $sidebar }}
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </x-container-fluid>
</x-layouts.app>