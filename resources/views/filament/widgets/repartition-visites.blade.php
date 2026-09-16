{{--
    Répartitions du rapport de visites.

    Les sections viennent de Filament, dont le CSS précompilé les couvre. La
    grille et les barres passent par des styles en ligne : une classe Tailwind
    écrite ici ne serait jamais générée, et une largeur calculée ne peut de
    toute façon pas s'exprimer en classe.
--}}
<div style="display: grid; gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));">
    @foreach ($this->blocs() as $bloc)
        <x-filament::section :heading="$bloc['titre']" :description="$bloc['aide']">
            @php
                $donnees = $bloc['donnees'];
                $somme = array_sum($donnees) ?: 1;
                $max = $donnees ? max($donnees) : 1;
            @endphp

            @if (empty($donnees))
                <p style="font-size: .875rem; opacity: .5;">Aucune donnée sur la période.</p>
            @else
                <ul style="display: flex; flex-direction: column; gap: .75rem;">
                    @foreach ($donnees as $libelle => $valeur)
                        <li>
                            <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: .875rem;">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                      title="{{ $libelle }}">{{ $libelle }}</span>
                                <span style="flex-shrink: 0; font-variant-numeric: tabular-nums; opacity: .7;">
                                    {{ number_format($valeur, 0, ',', ' ') }}
                                    <span style="opacity: .6;">({{ round($valeur / $somme * 100) }} %)</span>
                                </span>
                            </div>

                            {{-- La barre répète le chiffre affiché juste au-dessus :
                                 elle n'apporte rien à un lecteur d'écran. --}}
                            <div aria-hidden="true"
                                 style="margin-top: .35rem; height: .35rem; border-radius: 9999px;
                                        background: rgb(120 120 120 / .15); overflow: hidden;">
                                <div style="height: 100%; border-radius: 9999px;
                                            background: rgb(217 119 6);
                                            width: {{ round($valeur / $max * 100, 1) }}%;"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    @endforeach
</div>
