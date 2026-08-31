@props(['name', 'checked' => false])

<label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="peer sr-only">
    <span class="absolute inset-0 rounded-full bg-slate-200 transition-colors peer-checked:bg-cyan-500 peer-focus-visible:ring-2 peer-focus-visible:ring-cyan-500 peer-focus-visible:ring-offset-2"></span>
    <span class="absolute left-1 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
</label>
