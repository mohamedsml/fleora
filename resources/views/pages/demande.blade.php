{{--
    Page provisoire.

    Le formulaire en 3 étapes arrive au prochain chantier. Cette page existe
    dès maintenant pour que les CTA de la galerie mènent quelque part, et pour
    que le paramètre `creation` (la référence cliquée) soit déjà transmis.
--}}
@php
    $reference = request('creation');
    $creation = $reference
        ? \App\Models\Creation::publie()->where('slug_fr', $reference)->first()
        : null;
@endphp

<x-layout titre="Demande de soumission"
          description="Décrivez votre projet en quelques minutes. Réponse personnalisée sous 24 h.">

    <div class="mx-auto max-w-2xl px-6 py-24 text-center lg:py-32">

        <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">
            Racontez-nous votre projet
        </h1>

        @if ($creation)
            <p class="mt-6 text-lg text-ink-600">
                Vous vous inspirez de
                <span class="font-display text-ink-900">{{ $creation->t('titre') }}</span> —
                nous partirons de là.
            </p>
        @else
            <p class="mt-6 text-lg text-ink-600">
                Chaque création est unique. Dites-nous votre occasion, vos couleurs
                et le prénom à inscrire, et nous vous répondons sous 24 h.
            </p>
        @endif

        <div class="mt-12 rounded-3xl border border-sand-200 bg-ivory-100 p-10">
            <p class="text-ink-600">
                Le formulaire arrive très bientôt. En attendant, écrivez-nous
                directement — nous répondons aussi vite.
            </p>

            <div class="mt-8">
                <x-ui.bouton :href="'mailto:'.config('mail.from.address').($creation ? '?subject='.rawurlencode('Demande — '.$creation->t('titre')) : '')">
                    Nous écrire
                </x-ui.bouton>
            </div>
        </div>

        <a href="{{ route('creations') }}"
           class="mt-10 inline-block text-sm text-ink-400 underline underline-offset-4 transition hover:text-ink-700">
            {{ __('galerie.retour') }}
        </a>
    </div>
</x-layout>
