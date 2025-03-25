<div class="flex items-center text-gray-800 font-normal gap-1.5">
    <img alt="" class="rounded-full size-4 shrink-0"
        src="{{ Vite::image('flags/' . Str::of($country)->lower()->kebab() . '.svg') }}">
    {{ $country }}
</div>
