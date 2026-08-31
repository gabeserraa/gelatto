<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-[10px] border border-transparent bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800 focus:bg-navy-800 active:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
