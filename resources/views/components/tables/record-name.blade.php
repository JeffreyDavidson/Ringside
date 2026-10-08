@props(['record', 'href'])

{{-- Show routes 404 for soft-deleted records, so a deleted row's name is plain text. --}}
@if ($record->trashed())
    <span class="text-ringside-muted font-semibold wrap-break-word" data-test="deleted-record-name">{{ $slot }}</span>
@else
    <a
        href="{{ $href }}"
        class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
    >{{ $slot }}</a>
@endif
