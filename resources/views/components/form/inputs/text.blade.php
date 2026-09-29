@props(['label' => null])

<x-form.input type="text" :$label {{ $attributes }}>{{ $slot }}</x-form.input>
