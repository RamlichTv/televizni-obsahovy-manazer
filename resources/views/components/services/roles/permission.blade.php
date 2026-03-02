@props(['permission'])

<span class="inline-flex items-center rounded-full bg-amber-100 hover:bg-amber-200 px-3 py-1 text-xs font-medium text-amber-700">
    {{ $permission->name }}
</span>