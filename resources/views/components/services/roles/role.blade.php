@props(['role'])

<span class="inline-flex items-center gap-1.5 rounded-full bg-[#077CB6]/10 hover:bg-[#077CB6]/20 px-3 py-1 text-xs font-medium text-[#077CB6]">
    <span class="size-2.5 rounded-full border border-black/10" style="background-color: {{ $role->color }}"></span>
    {{ $role->name }}
</span>