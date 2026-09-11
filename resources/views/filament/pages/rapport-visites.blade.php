{{--
    Rapport de fréquentation.

    Le graphique est un SVG construit à la main plutôt qu'une bibliothèque :
    la CSP du site interdit les CDN externes, et une courbe à un point par jour
    ne justifie pas 200 Ko de JavaScript.
--}}
@php
    $resume = $this->resume();
    $serie = $this->evolutionQuotidienne();
    $max = max($serie ?: [0]) ?: 1;
@endphp

<x-filament-panels::page>

    {{-- ─── Période et export ─────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($this->periodes as $jours => $libelle)
                <button type="button"
                        wire:click="$set('periode', {{ $jours }})"
                        @class([
                            'rounded-lg px-3 py-2 text-sm transition',
                            'bg-primary-600 text-white' => $this->periode === $jours,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' => $this->periode !== $jours,
                        ])>
                    {{ $libelle }}
                </button>
            @endforeach
        </div>

        <x-filament::button wire:click="exporter" icon="heroicon-o-arrow-down-tray" color="gray">
            Exporter en CSV
        </x-filament::button>
    </div>

    {{-- ─── Chiffres clés ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($resume as $titre => $donnee)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $titre }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $donnee['valeur'] }}</p>

                @if ($donnee['evolution'] !== null)
                    <p @class([
                        'mt-1 text-xs font-medium',
                        'text-success-600' => $donnee['evolution'] >= 0,
                        'text-danger-600' => $donnee['evolution'] < 0,
                    ])>
                        {{ $donnee['evolution'] >= 0 ? '+' : '' }}{{ $donnee['evolution'] }} % vs période précédente
                    </p>
                @endif

                <p class="mt-1 text-xs text-gray-400">{{ $donnee['aide'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ─── Évolution quotidienne ─────────────────────────────────────── --}}
    <x-filament::section heading="Évolution des visites">
        @if (array_sum($serie) === 0)
            <p class="text-sm text-gray-500">Aucune visite sur la période.</p>
        @else
            @php
                $points = [];
                $n = max(count($serie) - 1, 1);
                $i = 0;
                foreach ($serie as $valeur) {
                    $x = round($i / $n * 100, 2);
                    $y = round(100 - ($valeur / $max * 100), 2);
                    $points[] = "{$x},{$y}";
                    $i++;
                }
                $ligne = implode(' ', $points);
            @endphp

            <svg viewBox="0 0 100 100" preserveAspectRatio="none"
                 class="h-48 w-full" role="img"
                 aria-label="Visites par jour sur la période, maximum {{ $max }}">
                {{-- Aire sous la courbe : rend la tendance lisible d'un coup d'œil. --}}
                <polygon points="0,100 {{ $ligne }} 100,100"
                         class="fill-primary-500/10" />
                <polyline points="{{ $ligne }}" fill="none"
                          class="stroke-primary-600" stroke-width="0.6"
                          vector-effect="non-scaling-stroke" />
            </svg>

            <div class="mt-2 flex justify-between text-xs text-gray-400">
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($serie))->translatedFormat('j M') }}</span>
                <span>Maximum : {{ $max }} visites/jour</span>
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($serie))->translatedFormat('j M') }}</span>
            </div>
        @endif
    </x-filament::section>

    {{-- ─── Parcours ──────────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Pages d'entrée"
                             description="Par où les visiteuses arrivent sur le site.">
            <x-rapport.liste :donnees="$this->pagesEntree()" vide="Aucune donnée sur la période." />
        </x-filament::section>

        <x-filament::section heading="Pages les plus vues"
                             description="Toutes visites confondues.">
            <x-rapport.liste :donnees="$this->pagesVues()" vide="Aucune donnée sur la période." />
        </x-filament::section>
    </div>

    {{-- ─── Acquisition et appareils ──────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section heading="Sources"
                             description="Site d'où vient la visiteuse.">
            <x-rapport.liste :donnees="$this->sources()" vide="Tout le trafic est direct." />
        </x-filament::section>

        <x-filament::section heading="Appareils">
            <x-rapport.liste :donnees="$this->appareils()" vide="Aucune donnée." />
        </x-filament::section>

        <x-filament::section heading="Langues">
            <x-rapport.liste :donnees="$this->langues()" vide="Aucune donnée." />
        </x-filament::section>
    </div>

    <p class="text-xs text-gray-400">
        Aucune donnée personnelle n'est conservée : ni adresse IP, ni témoin, ni identifiant
        persistant. Les visiteurs sont distingués par une empreinte renouvelée chaque jour.
    </p>

</x-filament-panels::page>
