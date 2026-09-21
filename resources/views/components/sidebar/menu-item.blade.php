@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
    'nested' => false,
    'deep' => false,
])

@if ($nested)
    {{-- Nested Menu Item Link --}}
    <div>
        <a
            href="{{ $href }}"
            tabindex="0"
            @class([
                'flex border border-transparent items-center grow ps-[10px] pe-[10px] py-[5px] rounded-lg',
                'gap-[5px]' => $deep,
                'gap-[14px]' => ! $deep,
                'bg-secondary-active' => $active,
                'hover:bg-secondary-active' => ! $active,
            ])
        >
            <span
                @class([
                    'flex w-[6px] -start-[3px] relative',
                    'before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-y-1/2',
                    'before:bg-primary' => $active,
                    'hover:before:bg-primary' => ! $active,
                ])
            ></span>

            <span @class([
                'text-2sm text-foreground hover:text-primary',
                'font-semibold text-primary' => $active,
                'font-normal' => ! $active,
            ])>
                {{ $slot }}
            </span>
        </a>
    </div>
@else
    {{-- Top-Level Menu Item Link --}}
    <div>
        <a
            href="{{ $href }}"
            tabindex="0"
            class="flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] ps-[10px] pe-[10px]"
        >
            @if ($icon)
                <span class="flex w-[20px] shrink-0 items-center text-gray-500"> {{ $icon }} </span>
            @endif

            <span @class([
                'text-sm text-foreground hover:text-primary',
                'group-data-[collapsed=true]:opacity-0 group-hover:opacity-100 transition-opacity duration-200',
                'font-semibold text-primary' => $active,
                'font-medium' => ! $active,
            ])>
                {{ $slot }}
            </span>
        </a>
    </div>
@endif
