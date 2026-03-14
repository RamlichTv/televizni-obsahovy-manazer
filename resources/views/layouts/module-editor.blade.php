{{--
    Dedicated full-screen shell for module authoring and preview pages.
    It intentionally skips the standard app navigation so module routes can behave like a focused editor workspace.
    The global feedback hosts still live here so editor actions share the same light-mode toast and modal system.
--}}

@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen overflow-hidden bg-gray-100 font-sans antialiased text-gray-900">
        <x-loading-screen />

        <div class="module-editor-layout h-screen w-screen overflow-hidden bg-gray-100 text-gray-900">
            {{ $slot }}
        </div>

        {{-- Shared feedback hosts keep editor workflows aligned with the rest of the application. --}}
        <x-toast />
        <x-modal kind="system" />
    </body>
</html>
