@props([
    'titre' => null,
    'description' => null,
    // Retire la page de l'index Google. Pour une page vide ou une confirmation :
    // une page sans contenu indexée dégrade la qualité perçue du domaine entier.
    'noindex' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Un onglet n'affiche qu'une vingtaine de caractères dès que plusieurs
         sont ouverts. Le nom vient donc en premier : placé à la fin, il était
         systématiquement tronqué. L'accueil porte la promesse commerciale, les
         autres pages leur libellé court. --}}
    <title>{{ $titre ? config('app.name').' — '.$titre : config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @endif

    {{-- Open Graph : contrôle l'aperçu quand un lien est partagé sur
         Facebook, Instagram ou par message — canal de découverte principal
         pour ce type de produit. --}}
    <meta property="og:type" content="website">
    {{-- og:site_name porte la marque dans l'aperçu partagé : le titre reste
         donc court et descriptif, sans répéter « Fleora ». --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $titre ?? config('app.name') }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">

    {{-- Favicons. Le SVG est servi en premier aux navigateurs qui le gèrent :
         net à toutes les tailles, contrairement aux PNG. Le .ico reste pour
         les anciens navigateurs et les raccourcis Windows. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Teinte la barre d'adresse sur mobile : le brun-taupe de la marque. --}}
    <meta name="theme-color" content="#67523e">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire fournit Alpine à TOUTES les pages, y compris celles sans
         composant Livewire : l'en-tête utilise Alpine pour son menu mobile.
         Sans cette directive, Livewire ne s'injecte que sur les pages qui
         portent un composant, et le menu casse ailleurs. --}}
    @livewireStyles

    {{-- Données structurées : permet à Google d'afficher les coordonnées et
         la zone desservie directement dans les résultats de recherche. --}}
    {{ $schema ?? '' }}
</head>
<body class="bg-ivory-50 text-ink-800 antialiased">
    {{-- Lien d'évitement : première cible au clavier, il permet de sauter la
         navigation pour atteindre le contenu. Invisible jusqu'au focus. --}}
    <a href="#contenu"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-full focus:bg-ink-900 focus:px-5 focus:py-2.5 focus:text-sm focus:text-ivory-50">
        Aller au contenu
    </a>

    <x-site.entete />

    <main id="contenu">
        {{ $slot }}
    </main>

    <x-site.pied />

    {{-- En fin de body : le DOM doit exister avant qu'Alpine ne s'y branche. --}}
    @livewireScripts
</body>
</html>
