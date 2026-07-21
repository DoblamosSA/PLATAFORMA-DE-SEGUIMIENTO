<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-slate-600 focus:bg-gray-700 dark:focus:bg-slate-600 active:bg-gray-900 dark:active:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 active:scale-[0.98] transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
