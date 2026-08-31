<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-[10px] border border-transparent bg-red-600 px-4 py-2 text-[13px] font-semibold text-white hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
