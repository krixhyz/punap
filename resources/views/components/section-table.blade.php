@props(['data', 'empty' => 'No records found.'])

<table class="w-full text-sm text-left text-gray-600 border-collapse">
    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
        {{ $header }}
    </thead>

    <tbody>
        @if ($data->isEmpty())
            <tr>
                <td colspan="10" class="text-center py-6 text-gray-500">{{ $empty }}</td>
            </tr>
        @else
            {{ $slot }}
        @endif
    </tbody>
</table>
