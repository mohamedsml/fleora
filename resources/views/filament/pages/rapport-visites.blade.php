{{--
    Rapport de fréquentation.

    La page ne porte plus aucun balisage propre : le filtre de période et les
    widgets sont rendus par Filament, dont le CSS précompilé les couvre. Une
    version antérieure écrivait ses classes Tailwind à la main — elles
    n'étaient jamais générées, et la page s'affichait en texte brut.
--}}
<x-filament-panels::page>
    {{ $this->filtersForm }}

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="['filters' => $this->filters ?? null]"
        :widgets="$this->getWidgets()"
    />

    <p class="fi-color-gray" style="font-size: .75rem; opacity: .6;">
        Aucune donnée personnelle n’est conservée : ni adresse IP, ni témoin, ni
        identifiant persistant. Les visiteurs sont distingués par une empreinte
        renouvelée chaque jour.
    </p>
</x-filament-panels::page>
