<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fafaf9" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
        <meta name="color-scheme" content="light dark">
        <title>{{ config('app.name', 'Malu') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
        <div id="app"></div>
    </body>
</html>
