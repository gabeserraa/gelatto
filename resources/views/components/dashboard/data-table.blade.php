@props(['headers' => [], 'paginator' => null])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-card border border-slate-200 bg-white shadow-card']) }}>
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50/60">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            {{ $slot }}
        </tbody>
    </table>
</div>

@if ($paginator)
    <div class="mt-4">
        {{ $paginator->links() }}
    </div>
@endif
