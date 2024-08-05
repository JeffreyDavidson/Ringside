@aware(['selected' => []])

<div class="card-toolbar">
    {{ $slot }}

    @if (count($selected) > 0)
        <x-buttons.delete-selected :selected=$selected />
    @endif
</div>
