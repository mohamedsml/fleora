@php
    // Groupées par catégorie : une liste de quinze questions à plat se parcourt
    // mal, alors qu'on cherche presque toujours dans un thème précis.
    $groupes = $faqs->groupBy('categorie');

    // schema.org FAQPage : permet à Google d'afficher les questions en accordéon
    // directement dans les résultats de recherche.
    $donneesStructurees = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->t('question'),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq->t('reponse'),
            ],
        ])->values()->all(),
    ];
@endphp

<x-layout titre="Questions fréquentes"
          description="Délais, livraison, personnalisation, budget : les réponses aux questions qu'on nous pose le plus souvent.">

    @if ($faqs->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode($donneesStructurees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif

    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">

        <header class="max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                Questions fréquentes
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-600">
                Si votre question n'y figure pas, écrivez-nous — nous répondons à
                chacune.
            </p>
        </header>

        @if ($faqs->isEmpty())
            <p class="mt-16 text-ink-400">Les questions fréquentes arrivent bientôt.</p>
        @else
            <div class="mt-16 space-y-14">
                @foreach ($groupes as $categorie => $questions)
                    <section>
                        @if ($categorie)
                            <h2 class="font-display text-2xl text-ink-900">{{ $categorie }}</h2>
                        @endif

                        <div class="mt-6 divide-y divide-sand-200 border-y border-sand-200">
                            @foreach ($questions as $faq)
                                {{-- <details> natif plutôt qu'un accordéon en JS :
                                     fonctionne sans JavaScript, reste accessible au
                                     clavier, et le contenu est lisible par Google
                                     même replié. --}}
                                <details class="group py-5">
                                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 text-ink-900">
                                        <span class="text-lg">{{ $faq->t('question') }}</span>
                                        <svg class="mt-1 h-5 w-5 flex-none text-ink-400 transition-transform group-open:rotate-45"
                                             fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </summary>
                                    <div class="mt-4 leading-relaxed text-ink-600">
                                        {!! nl2br(e($faq->t('reponse'))) !!}
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

        <div class="mt-20 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">Votre question n'y est pas ?</h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                Écrivez-nous, ou décrivez directement votre projet — c'est souvent
                le plus rapide.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <x-ui.bouton :href="route('demande')">Demander une soumission</x-ui.bouton>
                <x-ui.bouton :href="route('contact')" variante="secondaire">Nous écrire</x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
