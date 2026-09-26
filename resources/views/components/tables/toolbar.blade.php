@props(['id', 'model', 'value' => '', 'label', 'placeholder', 'clearLabel'])

<div
    class="border-ringside-line flex flex-wrap items-center justify-between gap-3 border-b p-4"
    data-test="table-toolbar"
>
    <x-tables.search-field :$id :$model :$value :$label :$placeholder :clear-label="$clearLabel" />
    <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
</div>
