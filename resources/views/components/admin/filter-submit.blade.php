@props(['label' => 'Tampilkan Data'])

<div {{ $attributes->merge(['class' => 'flex justify-end pt-4 mt-2 border-t border-slate-200']) }}>
    <button
        type="submit"
        class="inline-flex items-center justify-center px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg shadow-sm ring-1 ring-teal-700/20"
    >
        {{ $label }}
    </button>
</div>
