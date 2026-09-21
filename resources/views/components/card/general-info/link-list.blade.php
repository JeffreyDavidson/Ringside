@props(['label'])

<tr>
    <td class="pe-4 pb-3 text-sm text-gray-600 lg:pe-8">{{ $label }}:</td>
    <td class="pb-3 text-sm text-gray-900">
        <div class="space-y-1">{{ $slot }}</div>
    </td>
</tr>
