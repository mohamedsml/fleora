@props([
    'titre' => null,
    'description' => null,
    // Retire la page de l'index Google. Pour une page vide ou une confirmation :
    // une page sans contenu indexée dégrade la qualité perçue du domaine entier.
    'noindex' => false,
    // Entité affichée sur une page de détail. Alimente d'un seul coup le
    // sélecteur de langue, les hreflang et l'image de partage — trois calculs
    // séparés finiraient par diverger.
    'modele' => null,
    // Image de partage spécifique. À défaut, la photo du modèle, puis l'image
    // de marque.
    'image' => null,
])

@php
    $traductions = app(\App\Support\Traductions::class)->pour($modele);

    // og:image : photo de l'entité si elle en a une, sinon l'image de marque.
    // Un lien partagé sans visuel perd l'essentiel de son pouvoir d'attraction
    // sur Instagram et Messenger, premiers canaux de découverte ici.
    $imagePartage = $image
        ?? $modele?->media?->first()?->urlVariante(1200)
        ?? asset('images/og-fleora.jpg');
@endphp

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
    <meta property="og:url" content="{{ $traductions->canonique() }}">
    <meta property="og:image" content="{{ $imagePartage }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $traductions->codeRegional(app()->getLocale())) }}">

    {{-- Twitter reprend og: quand twitter: manque, mais le format large doit
         être déclaré explicitement, sinon l'aperçu reste une vignette. --}}
    <meta name="twitter:card" content="summary_large_image">

    {{-- Canonique : une seule URL fait foi pour un contenu donné. Sans elle,
         un slug non canonique ou un paramètre de suivi créerait un doublon
         dans l'index de Google. --}}
    <link rel="canonical" href="{{ $traductions->canonique() }}">

    {{-- hreflang réciproques : chaque version déclare toutes les autres,
         elle-même comprise. Un bloc non réciproque est purement ignoré par
         Google. x-default pointe vers le français, marché principal. --}}
    @foreach ($traductions->alternatives() as $langue => $url)
        <link rel="alternate" hreflang="{{ $traductions->codeRegional($langue) }}" href="{{ $url }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $traductions->alternatives()[$traductions->langueParDefaut()] }}">

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
        {{ __('commun.accessibilite.aller_contenu') }}
    </a>

    <x-site.entete />

    <main id="contenu">
        {{ $slot }}
    </main>

    <x-site.pied />

    {{-- En fin de body : le DOM doit exister avant qu'Alpine ne s'y branche. --}}
    @livewireScripts

    {{-- Script Turnstile, chargé uniquement si les clés sont configurées : une
         requête vers Cloudflare sur chaque page serait du poids inutile tant
         que le captcha n'est pas actif. --}}
    @if (\App\Livewire\FormulaireDemande::turnstileActif())
        <x-turnstile.scripts />
    @endif
</body>
</html>
