<div
    x-data="{ show: false, type: 'success', message: '' }"
    x-on:toast.window="show = true; type = $event.detail.type; message = $event.detail.message; setTimeout(() => show = false, 4000)"
    x-show="show"
    x-transition
    x-cloak
    class="fixed bottom-4 right-4 z-50 rounded-[10px] px-4 py-3 text-sm font-medium text-white shadow-lg"
    :class="type === 'success' ? 'bg-emerald-600' : 'bg-red-600'"
>
    <span x-text="message"></span>
</div>
