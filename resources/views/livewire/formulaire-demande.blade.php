@php
    $maxImages = config('fleora.demandes.max_pieces_jointes');
    $maxMo = config('fleora.demandes.max_piece_jointe_mo');
@endphp

<div class="mx-auto max-w-2xl">

    {{-- ── Progression ─────────────────────────────────────────────────── --}}
    <div class="mb-12">
        <div class="flex items-center justify-between text-xs uppercase tracking-widest">
            @foreach (__('demande.etapes') as $numero => $libelle)
                <span @class([
                    'transition',
                    'text-ink-900' => $etape >= $numero,
                    'text-ink-300' => $etape < $numero,
                ])>{{ $libelle }}</span>
            @endforeach
        </div>

        <div class="mt-3 h-0.5 overflow-hidden rounded-full bg-sand-200">
            <div class="h-full bg-ink-900 transition-all duration-500"
                 style="width: {{ ($etape / 3) * 100 }}%"></div>
        </div>

        <p class="mt-2 text-xs text-ink-400" aria-live="polite">
            {{ __('demande.etape_sur', ['courante' => $etape, 'total' => 3]) }}
        </p>
    </div>

    @if ($creationReference)
        <div class="mb-10 rounded-2xl bg-ivory-100 px-6 py-4 text-sm text-ink-600">
            {!! __('demande.inspire_de', [
                'creation' => '<span class="font-display text-lg text-ink-900">'.e($creationReference->t('titre')).'</span>',
            ]) !!}
        </div>
    @endif

    <form wire:submit="envoyer" class="space-y-10">

        {{-- Champ leurre : masqué visuellement mais présent dans le DOM.
             Un humain ne le remplit jamais, un bot remplit tout. --}}
        <div class="absolute -left-[9999px]" aria-hidden="true">
            <label for="site_web">Site web</label>
            <input type="text" id="site_web" wire:model="site_web" tabindex="-1" autocomplete="off">
        </div>

        {{-- ══════════════ ÉTAPE 1 — LE PROJET ══════════════════════════ --}}
        @if ($etape === 1)
            <fieldset>
                <legend class="font-display text-2xl text-ink-900">{{ __('demande.occasion') }}</legend>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($occasions as $o)
                        <button type="button"
                                wire:click="$set('occasion_id', {{ $o->id }})"
                                @class([
                                    'rounded-xl border px-4 py-3 text-sm transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => $occasion_id === $o->id,
                                    'border-sand-200 text-ink-600 hover:border-sand-300 hover:bg-ivory-100' => $occasion_id !== $o->id,
                                ])>
                            {{ $o->t('nom') }}
                        </button>
                    @endforeach

                    <button type="button"
                            wire:click="$set('occasion_id', null)"
                            @class([
                                'rounded-xl border px-4 py-3 text-sm transition',
                                'border-ink-900 bg-ink-900 text-ivory-50' => $occasion_id === null,
                                'border-sand-200 text-ink-600 hover:border-sand-300 hover:bg-ivory-100' => $occasion_id !== null,
                            ])>
                        {{ __('demande.autre') }}
                    </button>
                </div>

                @if ($occasion_id === null)
                    <input type="text" wire:model="occasion_autre"
                           placeholder="{{ __('demande.occasion_autre') }}"
                           class="mt-3 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                @endif
            </fieldset>

            <div>
                <label for="date_evenement" class="font-display text-2xl text-ink-900">
                    {{ __('demande.date') }}
                </label>
                <input type="date" id="date_evenement" wire:model.live="date_evenement"
                       min="{{ now()->toDateString() }}"
                       class="mt-4 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                <p class="mt-2 text-sm text-ink-400">{{ __('demande.date_aide') }}</p>

                {{-- Alerte douce : mieux vaut annoncer le délai serré tout de
                     suite que décevoir après coup. --}}
                @if ($this->delaiServe)
                    <p class="mt-3 rounded-lg bg-blush-50 px-4 py-3 text-sm text-terracotta-700">
                        {{ __('demande.delai_serre') }}
                    </p>
                @endif

                @error('date_evenement')
                    <p class="mt-2 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">{{ __('demande.type') }}</legend>

                <div class="mt-5 space-y-3">
                    @foreach ($types as $t)
                        <button type="button"
                                wire:click="$set('product_type_id', {{ $t->id }}); $set('type_indecis', false)"
                                @class([
                                    'block w-full rounded-xl border px-5 py-4 text-left transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => ! $type_indecis && $product_type_id === $t->id,
                                    'border-sand-200 hover:border-sand-300 hover:bg-ivory-100' => $type_indecis || $product_type_id !== $t->id,
                                ])>
                            <span class="block text-sm">{{ $t->t('nom') }}</span>
                            @if ($t->t('description'))
                                <span @class([
                                    'mt-0.5 block text-xs',
                                    'text-ivory-200/70' => ! $type_indecis && $product_type_id === $t->id,
                                    'text-ink-400' => $type_indecis || $product_type_id !== $t->id,
                                ])>{{ $t->t('description') }}</span>
                            @endif
                        </button>
                    @endforeach

                    {{-- « Je ne sais pas encore » : ne jamais bloquer l'indécis,
                         c'est souvent une cliente sérieuse qui découvre. --}}
                    <button type="button"
                            wire:click="$set('type_indecis', true); $set('product_type_id', null)"
                            @class([
                                'block w-full rounded-xl border px-5 py-4 text-left transition',
                                'border-ink-900 bg-ink-900 text-ivory-50' => $type_indecis,
                                'border-dashed border-sand-300 hover:bg-ivory-100' => ! $type_indecis,
                            ])>
                        <span class="block text-sm">{{ __('demande.type_indecis') }}</span>
                        <span @class([
                            'mt-0.5 block text-xs',
                            'text-ivory-200/70' => $type_indecis,
                            'text-ink-400' => ! $type_indecis,
                        ])>{{ __('demande.type_indecis_aide') }}</span>
                    </button>
                </div>
            </fieldset>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">{{ __('demande.quantite') }}</legend>

                {{-- Boutons plutôt qu'un champ numérique : plus rapide au pouce. --}}
                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach (__('demande.quantites') as $valeur => $libelle)
                        <button type="button" wire:click="$set('quantite', '{{ $valeur }}')"
                                @class([
                                    'rounded-full border px-5 py-2.5 text-sm transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => $quantite === (string) $valeur,
                                    'border-sand-200 text-ink-600 hover:border-sand-300' => $quantite !== (string) $valeur,
                                ])>
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </fieldset>
        @endif

        {{-- ══════════════ ÉTAPE 2 — PERSONNALISATION ═══════════════════ --}}
        @if ($etape === 2)
            <div>
                <label for="texte_a_inscrire" class="font-display text-2xl text-ink-900">
                    {{ __('demande.texte') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </label>
                <input type="text" id="texte_a_inscrire" wire:model.live="texte_a_inscrire"
                       maxlength="60" placeholder="{{ __('demande.texte_placeholder') }}"
                       class="mt-4 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">

                {{-- Aperçu en direct dans la typo du produit : détail à faible
                     coût et fort effet sur l'envie de terminer. --}}
                @if ($texte_a_inscrire)
                    <div class="mt-4 rounded-xl bg-gradient-to-br from-blush-50 to-ivory-100 px-6 py-8 text-center">
                        <p class="text-xs uppercase tracking-widest text-ink-400">{{ __('demande.texte_apercu') }}</p>
                        <p class="mt-2 font-display text-4xl text-ink-900">{{ $texte_a_inscrire }}</p>
                    </div>
                @endif
            </div>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">
                    {{ __('demande.couleurs') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </legend>
                <p class="mt-1 text-sm text-ink-400">{{ __('demande.couleurs_aide') }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach ($palettes as $cle => $palette)
                        <button type="button" wire:click="basculerCouleur('{{ $cle }}')"
                                @class([
                                    'flex items-center gap-2.5 rounded-full border py-2 pl-2 pr-4 text-sm transition',
                                    'border-ink-900 bg-ivory-100' => in_array($cle, $couleurs, true),
                                    'border-sand-200 hover:border-sand-300' => ! in_array($cle, $couleurs, true),
                                ])
                                aria-pressed="{{ in_array($cle, $couleurs, true) ? 'true' : 'false' }}">
                            <span class="h-6 w-6 rounded-full border border-ink-900/10"
                                  style="background-color: {{ $palette['hex'] }}"></span>
                            {{ $palette['libelle'] }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">
                    {{ __('demande.theme') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </legend>

                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach (__('demande.themes') as $valeur => $libelle)
                        <button type="button" wire:click="$set('theme', '{{ $valeur }}')"
                                @class([
                                    'rounded-full border px-5 py-2.5 text-sm transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => $theme === $valeur,
                                    'border-sand-200 text-ink-600 hover:border-sand-300' => $theme !== $valeur,
                                ])>
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">{{ __('demande.fleurs') }}</legend>

                <div class="mt-5 space-y-3">
                    @foreach (__('demande.fleurs_options') as $valeur => $libelle)
                        <label @class([
                            'flex cursor-pointer items-center gap-3 rounded-xl border px-5 py-4 text-sm transition',
                            'border-ink-900 bg-ivory-100' => $fleurs === $valeur,
                            'border-sand-200 hover:bg-ivory-100' => $fleurs !== $valeur,
                        ])>
                            <input type="radio" wire:model="fleurs" value="{{ $valeur }}"
                                   class="border-sand-300 text-ink-900 focus:ring-sage-400">
                            {{ $libelle }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="font-display text-2xl text-ink-900">
                    {{ __('demande.budget') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </legend>
                {{-- Jamais obligatoire : beaucoup abandonnent plutôt que de
                     s'engager sur un chiffre. Des tranches larges obtiennent
                     un bien meilleur taux de remplissage. --}}
                <p class="mt-1 text-sm text-ink-400">{{ __('demande.budget_aide') }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach (__('demande.budgets') as $valeur => $libelle)
                        <button type="button" wire:click="$set('budget', '{{ $valeur }}')"
                                @class([
                                    'rounded-full border px-5 py-2.5 text-sm transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => $budget === (string) $valeur,
                                    'border-sand-200 text-ink-600 hover:border-sand-300' => $budget !== (string) $valeur,
                                ])>
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <p class="font-display text-2xl text-ink-900">
                    {{ __('demande.inspirations') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </p>
                <p class="mt-1 text-sm text-ink-400">
                    {{ __('demande.inspirations_aide', ['max' => $maxImages, 'mo' => $maxMo]) }}
                </p>

                <label class="mt-4 flex cursor-pointer items-center justify-center rounded-xl border border-dashed border-sand-300 px-6 py-8 text-sm text-ink-500 transition hover:border-sand-400 hover:bg-ivory-100">
                    {{-- HEIC accepté : format par défaut des iPhone. --}}
                    <input type="file" wire:model="inspirations" multiple
                           accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                           class="sr-only">
                    <span wire:loading.remove wire:target="inspirations">{{ __('demande.inspirations_ajouter') }}</span>
                    <span wire:loading wire:target="inspirations">{{ __('demande.envoi_en_cours') }}</span>
                </label>

                @if ($inspirations)
                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($inspirations as $apercu)
                            @if (method_exists($apercu, 'temporaryUrl'))
                                <img src="{{ $apercu->temporaryUrl() }}" alt=""
                                     class="aspect-square w-full rounded-lg object-cover">
                            @endif
                        @endforeach
                    </div>
                @endif

                @error('inspirations.*')
                    <p class="mt-2 text-sm text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="commentaires" class="font-display text-2xl text-ink-900">
                    {{ __('demande.commentaires') }}
                    <span class="text-sm font-normal text-ink-400">({{ __('demande.optionnel') }})</span>
                </label>
                <textarea id="commentaires" wire:model="commentaires" rows="4"
                          placeholder="{{ __('demande.commentaires_placeholder') }}"
                          class="mt-4 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400"></textarea>
            </div>
        @endif

        {{-- ══════════════ ÉTAPE 3 — COORDONNÉES ════════════════════════ --}}
        @if ($etape === 3)
            <div class="grid gap-6 sm:grid-cols-2">
                {{-- Un seul champ pour le nom complet : deux champs séparés
                     n'apportent rien et doublent la friction. --}}
                <div class="sm:col-span-2">
                    <label for="nom" class="block text-sm text-ink-700">{{ __('demande.nom') }} *</label>
                    <input type="text" id="nom" wire:model.blur="nom" required autocomplete="name"
                           class="mt-2 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                    @error('nom')<p class="mt-1.5 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="courriel" class="block text-sm text-ink-700">{{ __('demande.courriel') }} *</label>
                    <input type="email" id="courriel" wire:model.blur="courriel" required autocomplete="email"
                           class="mt-2 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                    @error('courriel')<p class="mt-1.5 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="telephone" class="block text-sm text-ink-700">{{ __('demande.telephone') }}</label>
                    <input type="tel" id="telephone" wire:model.blur="telephone" autocomplete="tel"
                           placeholder="(514) 555-1234"
                           class="mt-2 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                    @error('telephone')<p class="mt-1.5 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="ville" class="block text-sm text-ink-700">{{ __('demande.ville') }}</label>
                    <input type="text" id="ville" wire:model.blur="ville"
                           placeholder="{{ __('demande.ville_placeholder') }}"
                           class="mt-2 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                </div>
            </div>

            <fieldset>
                <legend class="text-sm text-ink-700">{{ __('demande.moyen_prefere') }}</legend>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach (__('demande.moyens') as $valeur => $libelle)
                        <button type="button" wire:click="$set('moyen_prefere', '{{ $valeur }}')"
                                @class([
                                    'rounded-full border px-4 py-2 text-sm transition',
                                    'border-ink-900 bg-ink-900 text-ivory-50' => $moyen_prefere === $valeur,
                                    'border-sand-200 text-ink-600 hover:border-sand-300' => $moyen_prefere !== $valeur,
                                ])>
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            <div>
                {{-- Seul moyen de mesurer le bouche-à-oreille, invisible dans
                     n'importe quel outil d'analytique. --}}
                <label for="source" class="block text-sm text-ink-700">{{ __('demande.source') }}</label>
                <select id="source" wire:model="source"
                        class="mt-2 w-full rounded-lg border-sand-200 bg-ivory-50 px-4 py-3 text-ink-900 focus:border-sage-400 focus:ring-sage-400">
                    <option value="">—</option>
                    @foreach (__('demande.sources') as $valeur => $libelle)
                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Loi 25 : consentement explicite, jamais pré-coché. --}}
            <div class="space-y-4 border-t border-sand-200 pt-8">
                <label class="flex items-start gap-3 text-sm text-ink-600">
                    <input type="checkbox" wire:model="consentement" required
                           class="mt-0.5 rounded border-sand-300 text-ink-900 focus:ring-sage-400">
                    <span>
                        {{ __('demande.consentement') }}
                        <a href="{{ url('/confidentialite') }}" target="_blank" rel="noopener"
                           class="underline underline-offset-2 hover:text-ink-900">{{ __('demande.consentement_lien') }}</a>
                    </span>
                </label>
                @error('consentement')<p class="text-sm text-error-600">{{ $message }}</p>@enderror

                <label class="flex items-start gap-3 text-sm text-ink-600">
                    <input type="checkbox" wire:model="infolettre"
                           class="mt-0.5 rounded border-sand-300 text-ink-900 focus:ring-sage-400">
                    <span>{{ __('demande.infolettre') }}</span>
                </label>
            </div>
        @endif

        {{-- ── Navigation ──────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between border-t border-sand-200 pt-8">
            @if ($etape > 1)
                <button type="button" wire:click="precedent"
                        class="text-sm text-ink-500 transition hover:text-ink-900">
                    ← {{ __('demande.precedent') }}
                </button>
            @else
                <span></span>
            @endif

            @if ($etape < 3)
                <button type="button" wire:click="suivant"
                        class="rounded-full bg-ink-900 px-8 py-3.5 text-sm text-ivory-50 transition hover:bg-blush-600">
                    {{ __('demande.suivant') }}
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled"
                        class="rounded-full bg-ink-900 px-8 py-3.5 text-sm text-ivory-50 transition hover:bg-blush-600 disabled:opacity-60">
                    <span wire:loading.remove wire:target="envoyer">{{ __('demande.envoyer') }}</span>
                    <span wire:loading wire:target="envoyer">{{ __('demande.envoi_en_cours') }}</span>
                </button>
            @endif
        </div>
    </form>
</div>
