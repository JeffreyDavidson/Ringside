@props([
    'fluid' => false,
    'class' => '',
])

<div {{ $attributes->merge([
    'class' => $fluid 
        ? 'container-fluid w-full px-4 mx-auto ' . $class
        : 'container max-w-7xl px-4 mx-auto sm:px-6 lg:px-8 ' . $class
]) }}>
    {{ $slot }}
</div>