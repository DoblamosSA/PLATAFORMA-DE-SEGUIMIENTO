@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm']) }}>
