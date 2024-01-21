@aware(['selected' => []])

<div class="card-toolbar d-flex flex-row justify-content-end">
    {{ $slot }}

    @if (count($selected) > 0)
        <x-buttons.delete-selected :selected=$selected/>
    @endif
</div>
