@props(['label' => null, 'initialFocus' => false])

<x-form.input type="text" :$label :$initialFocus {{ $attributes }}>{{ $slot }}</x-form.input>
