@props(['label'])

<tr>
    <td class="text-ringside-muted pe-4 pb-3 text-sm lg:pe-8">{{ $label }}:</td>
    <td class="text-ringside-ink pb-3 text-sm">
        <div class="space-y-1">{{ $slot }}</div>
    </td>
</tr>
