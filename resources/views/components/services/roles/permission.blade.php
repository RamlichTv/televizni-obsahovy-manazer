@props(['permission'])

@php
    $permissionLabel = __($permission->name);
    if ($permissionLabel === $permission->name) {
        $permissionLabel = (string) \Illuminate\Support\Str::of($permission->name)->replace('_', ' ')->title();
    }
@endphp

<span class="inline-flex items-center rounded-full bg-amber-100 hover:bg-amber-200 px-3 py-1 text-xs font-medium text-amber-700">
    {{ $permissionLabel }}
</span>
