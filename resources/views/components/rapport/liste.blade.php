{{--
    Liste classée avec barre de proportion.

    La barre rend les écarts lisibles sans lire les chiffres : sur dix lignes,
    l'œil repère la dominante bien plus vite qu'en comparant des nombres.
--}}
@props([
    'donnees' => [],
    'vide' => 'Aucune donnée.',
])

@if (empty($donnees))
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $vide }}</p>
@else
    @php
        $total = max($donnees);
        $somme = array_sum($donnees);
    @endphp

    <ul class="space-y-2.5">
        @foreach ($donnees as $libelle => $valeur)
            <li>
                <div class="flex items-baseline justify-between gap-4 text-sm">
                    <span class="truncate text-gray-700 dark:text-gray-300" title="{{ $libelle }}">
                        {{ $libelle }}
                    </span>
                    <span class="shrink-0 tabular-nums text-gray-500">
                        {{ number_format($valeur, 0, ',', ' ') }}
                        <span class="text-xs text-gray-400">
                            ({{ $somme > 0 ? round($valeur / $somme * 100) : 0 }} %)
                        </span>
                    </span>
                </div>

                {{-- aria-hidden : le chiffre au-dessus porte déjà l'information,
                     la barre ne ferait que la répéter pour un lecteur d'écran. --}}
                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                     aria-hidden="true">
                    <div class="h-full rounded-full bg-primary-500"
                         style="width: {{ $total > 0 ? round($valeur / $total * 100, 1) : 0 }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
