{{--
    Galerie filtrable.

    La lightbox est gérée en Alpine au niveau du conteneur : un seul état pour
    toute la grille, plutôt qu'un composant par carte. Sur mobile, on ne
    l'ouvre pas — le tap mène à la page détail, qui est plus confortable et
    partageable.
--}}
<div x-data="lightbox()">

    {{-- ── Filtres ─────────────────────────────────────────────────────── --}}
    <div class="border-b border-sand-200 pb-8">

        {{-- Occasions. Défilement horizontal sur mobile plutôt qu'un menu
             déroulant : on voit les options sans avoir à ouvrir quoi que ce soit. --}}
        <div class="-mx-6 overflow-x-auto px-6 pb-1 sm:mx-0 sm:px-0
                    [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <div class="flex gap-2 whitespace-nowrap">
                <button type="button"
                        wire:click="reinitialiser"
                        @class([
                            'rounded-full px-4 py-2 text-sm transition',
                            'bg-ink-900 text-ivory-50' => ! $occasion && ! $type,
                            'bg-ivory-100 text-ink-600 hover:bg-sand-100' => $occasion || $type,
                        ])>
                    {{ __('galerie.toutes') }}
                </button>

                @foreach ($occasions as $o)
                    <button type="button"
                            wire:click="filtrerOccasion('{{ $o->slug_fr }}')"
                            @class([
                                'rounded-full px-4 py-2 text-sm transition',
                                'bg-ink-900 text-ivory-50' => $occasion === $o->slug_fr,
                                'bg-ivory-100 text-ink-600 hover:bg-sand-100' => $occasion !== $o->slug_fr,
                            ])>
                        {{ $o->t('nom') }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Types de produit --}}
        @if ($types->isNotEmpty())
            <div class="-mx-6 mt-3 overflow-x-auto px-6 sm:mx-0 sm:px-0
                        [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="flex gap-2 whitespace-nowrap">
                    @foreach ($types as $t)
                        <button type="button"
                                wire:click="filtrerType('{{ $t->slug_fr }}')"
                                @class([
                                    'rounded-full border px-4 py-1.5 text-xs uppercase tracking-widest transition',
                                    'border-terracotta-400 bg-terracotta-50 text-terracotta-700' => $type === $t->slug_fr,
                                    'border-sand-200 text-ink-400 hover:border-sand-300' => $type !== $t->slug_fr,
                                ])>
                            {{ $t->t('nom') }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Compteur : rassure sur l'effet du filtre, et signale un filtre
             trop restrictif avant que le visiteur ne conclue au site vide. --}}
        <p class="mt-5 text-sm text-ink-400" aria-live="polite">
            {{ trans_choice('galerie.compteur', $total, ['count' => $total]) }}
        </p>
    </div>

    {{-- ── Grille ──────────────────────────────────────────────────────── --}}
    @if ($creations->isEmpty())
        <div class="py-24 text-center">
            <p class="font-display text-2xl text-ink-600">{{ __('galerie.aucun_resultat') }}</p>
            <p class="mt-2 text-ink-400">{{ __('galerie.aucun_resultat_aide') }}</p>

            @if ($occasion || $type)
                <button type="button" wire:click="reinitialiser"
                        class="mt-6 text-sm text-terracotta-600 underline underline-offset-4 hover:text-terracotta-700">
                    {{ __('galerie.voir_toutes') }}
                </button>
            @endif
        </div>
    @else
        <div class="mt-12 grid grid-cols-1 gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($creations as $index => $creation)
                <x-site.carte-creation
                    :creation="$creation"
                    :index="$index"
                    lightbox />
            @endforeach
        </div>

        @if ($resteAAfficher > 0)
            <div class="mt-16 text-center">
                <button type="button"
                        wire:click="voirPlus"
                        wire:loading.attr="disabled"
                        class="rounded-full border border-sand-300 px-8 py-3 text-sm text-ink-700 transition hover:border-ink-900 hover:bg-ivory-100 disabled:opacity-50">
                    <span wire:loading.remove wire:target="voirPlus">
                        {{ __('galerie.voir_plus', ['reste' => $resteAAfficher]) }}
                    </span>
                    <span wire:loading wire:target="voirPlus">{{ __('galerie.chargement') }}</span>
                </button>
            </div>
        @endif
    @endif

    {{-- ── Lightbox (desktop) ──────────────────────────────────────────── --}}
    <div x-show="ouverte"
         x-cloak
         @keydown.escape.window="fermer()"
         @keydown.arrow-left.window="precedente()"
         @keydown.arrow-right.window="suivante()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/95 p-8 lg:flex"
         role="dialog"
         aria-modal="true"
         :aria-label="titre">

        <button type="button" @click="fermer()"
                class="absolute right-6 top-6 rounded-full p-3 text-ivory-200 transition hover:bg-ivory-50/10"
                aria-label="{{ __('galerie.fermer') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <button type="button" @click="precedente()" x-show="images.length > 1"
                class="absolute left-6 rounded-full p-3 text-ivory-200 transition hover:bg-ivory-50/10"
                aria-label="{{ __('galerie.precedente') }}">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>

        {{-- @click.stop : cliquer sur l'image ne doit pas fermer la lightbox,
             alors qu'un clic sur le fond la ferme. --}}
        <div class="max-h-full max-w-4xl" @click.stop>
            <img :src="image" :alt="titre"
                 class="max-h-[75vh] w-auto rounded-lg object-contain">

            <div class="mt-5 text-center">
                <h2 class="font-display text-2xl text-ivory-50" x-text="titre"></h2>
                <p class="mt-1 text-sm text-ivory-200/60" x-text="soustitre"></p>

                <a :href="lien"
                   class="mt-5 inline-block rounded-full bg-ivory-50 px-7 py-2.5 text-sm text-ink-900 transition hover:bg-ivory-200">
                    {{ __('galerie.cta_similaire') }}
                </a>
            </div>
        </div>

        <button type="button" @click="suivante()" x-show="images.length > 1"
                class="absolute right-6 rounded-full p-3 text-ivory-200 transition hover:bg-ivory-50/10"
                aria-label="{{ __('galerie.suivante') }}">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        {{-- Ferme au clic sur le fond. Placé APRÈS le contenu et en -z-10 pour
             ne pas intercepter les clics sur les boutons. --}}
        <div class="absolute inset-0 -z-10" @click="fermer()"></div>
    </div>
</div>

@script
<script>
    Alpine.data('lightbox', () => ({
        ouverte: false,
        index: 0,
        images: [],

        // La lightbox ne s'ouvre qu'à partir de 1024 px : sur mobile, une
        // superposition plein écran est moins confortable qu'une vraie page,
        // et la page détail est partageable.
        ouvrir(donnees) {
            if (window.innerWidth < 1024) return false;

            this.images = donnees;
            this.index = 0;
            this.ouverte = true;
            document.body.style.overflow = 'hidden';
            return true;
        },

        fermer() {
            this.ouverte = false;
            document.body.style.overflow = '';
        },

        suivante() { this.index = (this.index + 1) % this.images.length },
        precedente() { this.index = (this.index - 1 + this.images.length) % this.images.length },

        get courante() { return this.images[this.index] ?? {} },
        get image() { return this.courante.image ?? '' },
        get titre() { return this.courante.titre ?? '' },
        get soustitre() { return this.courante.soustitre ?? '' },
        get lien() { return this.courante.lien ?? '' },
    }));
</script>
@endscript
