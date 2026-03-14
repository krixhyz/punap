@props(['data', 'empty' => 'No records found.'])

<table class="w-full border-collapse text-left text-sm text-slate-600">
    <thead class="bg-slate-100 text-xs uppercase text-slate-700">
        {{ $header }}
    </thead>

    <tbody>
        @if ($data->isEmpty())
            <tr>
                <td colspan="10" class="py-6 text-center text-slate-500">{{ $empty }}</td>
            </tr>
        @else
            {{ $slot }}
        @endif
    </tbody>
</table>
