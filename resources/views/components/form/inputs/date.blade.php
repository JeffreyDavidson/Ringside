@props(['label' => null])

<x-form.input type="date" :$label {{ $attributes }}>{{ $slot }}</x-form.input>
