@props(['title', 'description', 'icon' => 'heroicon-o-inbox', 'descriptionClass' => ''])

<div {{ $attributes->class(['border-border flex flex-col items-center gap-3 border-t px-4 py-14 text-center sm:px-6']) }}>
    <x-dynamic-component :component="$icon" class="text-muted-foreground mb-1 size-8" aria-hidden="true" />
    <h2 class="text-foreground m-0 text-base font-semibold">{{ $title }}</h2>
    <p @class(['text-muted-foreground m-0 max-w-none text-sm leading-6 sm:whitespace-nowrap', $descriptionClass])>
        {{ $description }}
    </p>
    {{ $slot }}
</div>
