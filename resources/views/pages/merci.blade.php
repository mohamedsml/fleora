@php
    // Le numéro vient de la session : rafraîchir la page ne doit pas le perdre,
    // mais y arriver sans avoir envoyé de demande ne doit rien inventer.
    $numero = session('demande_numero');
@endphp

<x-layout :titre="__('merci.titre')" description="">
    {{-- Page de confirmation : elle sert aussi de repère de conversion pour
         l'analytique — une modale ne se mesure pas proprement. --}}
    <meta name="robots" content="noindex">

    <div class="mx-auto max-w-2xl px-6 py-24 text-center lg:py-32">

        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sage-100">
            <svg class="h-8 w-8 text-sage-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        <h1 class="mt-8 font-display text-4xl text-ink-900 sm:text-5xl">{{ __('merci.titre') }}</h1>

        <p class="mt-6 text-lg leading-relaxed text-ink-600">
            {{ __('merci.intro', ['delai' => config('fleora.contact.delai_reponse_h')]) }}
        </p>

        @if ($numero)
            <div class="mt-10 rounded-2xl border border-sand-200 bg-ivory-100 px-8 py-6">
                <p class="text-xs uppercase tracking-widest text-ink-400">{{ __('merci.numero') }}</p>
                <p class="mt-2 font-display text-3xl text-ink-900">{{ $numero }}</p>
                <p class="mt-3 text-sm text-ink-500">{{ __('merci.numero_aide') }}</p>
            </div>
        @endif

        <div class="mt-12 space-y-4 text-sm text-ink-500">
            <p>{{ __('merci.verifier_courriel') }}</p>
        </div>

        <div class="mt-12 flex flex-wrap justify-center gap-4">
            <x-ui.bouton :href="route('creations')" variante="secondaire">
                {{ __('merci.cta_creations') }}
            </x-ui.bouton>
        </div>
    </div>
</x-layout>
