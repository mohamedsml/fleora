<x-layout
    :titre="__('galerie.titre')"
    :description="__('galerie.intro')">

    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28">

        <header class="max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                {{ __('galerie.titre') }}
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-600">
                {{ __('galerie.intro') }}
            </p>
        </header>

        <div class="mt-14">
            <livewire:galerie />
        </div>
    </div>
</x-layout>
