@aware(['match'])

<p>
    @foreach ($match->titles as $title)
        <x-route-link :route="route('titles.show', $title)" label="{{ $title->name }}"/>

        @if (! $loop->last)
            @php echo " & " @endphp
        @endif
    @endforeach

    {{ str('Championship')->plural($match->titles->count()) }}
</p>
