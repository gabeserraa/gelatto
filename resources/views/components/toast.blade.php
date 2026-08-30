<div
    x-data="{ show: false, type: 'success', message: '' }"
    x-on:toast.window="show = true; type = $event.detail.type; message = $event.detail.message; setTimeout(() => show = false, 4000)"
    x-show="show"
    x-transition
    x-cloak
    class="fixed bottom-4 right-4 z-50 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
    :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
>
    <span x-text="message"></span>
</div>
