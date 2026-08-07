<div class="flex items-center gap-1.5 font-normal text-gray-800">
    <img
        alt=""
        class="size-4 shrink-0 rounded-full"
        src="{{ Vite::image('flags/' . Str::of($country)->lower()->kebab() . '.svg') }}"
    />
    {{ $country }}
</div>
