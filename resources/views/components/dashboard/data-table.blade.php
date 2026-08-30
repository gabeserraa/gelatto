@props(['headers' => [], 'paginator' => null])

<div class="overflow-x-auto rounded-lg bg-white shadow">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>

@if ($paginator)
    <div class="mt-4">
        {{ $paginator->links() }}
    </div>
@endif
