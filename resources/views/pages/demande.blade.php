<x-layout :titre="__('demande.titre')" :description="__('demande.intro')">

    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">

        <header class="mb-14 text-center">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">
                {{ __('demande.titre') }}
            </h1>
            <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-ink-600">
                {{ __('demande.intro') }}
            </p>
        </header>

        <livewire:formulaire-demande :creation="request('creation')" />
    </div>
</x-layout>
