<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="motion-safe:scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fuentes de la marca: Manrope (texto), Bricolage Grotesque (títulos) y Caveat Brush (acento manuscrito) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Caveat+Brush&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Fondo de la página antes de que carguen los estilos y React (KAN-349):
             sin esto se ve un fogonazo blanco al entrar. Mismo color que marca.fondo
             en tailwind.config.js. -->
        <style>html, body { background-color: #E8EEF5; }</style>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
