@props(['label'])

<tr>
    <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-8">
        {{ $label }}:
    </td>
    <td class="text-sm text-gray-900 pb-3">
        {{ $slot }}
    </td>
</tr>
