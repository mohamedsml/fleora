@props([
    'titre' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $titre ? $titre.' — '.config('app.name') : config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    {{-- Open Graph : contrôle l'aperçu quand un lien est partagé sur
         Facebook, Instagram ou par message — canal de découverte principal
         pour ce type de produit. --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $titre ?? config('app.name') }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">

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
