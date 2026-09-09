@php
    $liens = [
        ['url' => url('/'), 'libelle' => 'Accueil'],
        ['url' => url('/creations'), 'libelle' => 'Créations'],
        ['url' => url('/occasions'), 'libelle' => 'Occasions'],
        ['url' => url('/a-propos'), 'libelle' => 'À propos'],
        ['url' => url('/contact'), 'libelle' => 'Contact'],
    ];
@endphp

{{-- x-data d'Alpine, fourni par Livewire : suffisant pour un menu mobile,
     inutile d'y consacrer un composant Livewire (aucun aller-retour serveur). --}}
<header x-data="{ ouvert: false }"
        class="sticky top-0 z-40 border-b border-ink-800/5 bg-ivory-50/85 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
        <a href="{{ url('/') }}" class="font-display text-3xl tracking-tight text-ink-900">
            {{ config('app.name') }}
        </a>

        <nav class="hidden items-center gap-9 lg:flex" aria-label="Navigation principale">
            @foreach ($liens as $lien)
                <a href="{{ $lien['url'] }}"
                   @class([
                       'text-sm tracking-wide transition-colors hover:text-blush-600',
                       'text-blush-700' => request()->url() === $lien['url'],
                       'text-ink-600' => request()->url() !== $lien['url'],
                   ])
                   @if (request()->url() === $lien['url']) aria-current="page" @endif>
                    {{ $lien['libelle'] }}
                </a>
            @endforeach

            <x-ui.bouton href="{{ url('/demande') }}" class="!px-6 !py-2.5">
                Demander une soumission
            </x-ui.bouton>
        </nav>

        <button type="button"
                @click="ouvert = ! ouvert"
                :aria-expanded="ouvert ? 'true' : 'false'"
                aria-controls="menu-mobile"
                class="-mr-2 p-2 text-ink-800 lg:hidden">
            <span class="sr-only">Ouvrir le menu</span>
            {{-- Deux tracés superposés : le second reste masqué tant qu'Alpine
                 n'a pas démarré, sinon les deux icônes se chevauchent. --}}
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path x-show="! ouvert" stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                <path x-show="ouvert" x-cloak stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div id="menu-mobile" x-show="ouvert" x-cloak x-collapse class="border-t border-ink-800/5 lg:hidden">
        <nav class="space-y-1 px-6 py-5" aria-label="Navigation mobile">
            @foreach ($liens as $lien)
                <a href="{{ $lien['url'] }}" class="block py-2.5 text-ink-700 hover:text-blush-600">
                    {{ $lien['libelle'] }}
                </a>
            @endforeach

            <x-ui.bouton href="{{ url('/demande') }}" class="mt-4 w-full">
                Demander une soumission
            </x-ui.bouton>
        </nav>
    </div>
</header>
