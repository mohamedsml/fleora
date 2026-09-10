<x-mail::message>
# {{ __('courriels.confirmation.titre', ['prenom' => Str::before($demande->nom, ' ')]) }}

{{ __('courriels.confirmation.intro', ['delai' => $delai]) }}

<x-mail::panel>
**{{ __('courriels.champs.numero') }} : {{ $demande->numero_suivi }}**
</x-mail::panel>

## {{ __('courriels.confirmation.recapitulatif') }}

@if ($demande->occasionLibelle())
**{{ __('courriels.champs.occasion') }}** — {{ $demande->occasionLibelle() }}
@endif

@if ($demande->date_evenement)
**{{ __('courriels.champs.date') }}** — {{ $demande->date_evenement->translatedFormat('j F Y') }}
@endif

@if ($demande->productType)
**{{ __('courriels.champs.type') }}** — {{ $demande->productType->t('nom') }}
@endif

@if ($demande->quantite)
**{{ __('courriels.champs.quantite') }}** — {{ __('demande.quantites.'.$demande->quantite) }}
@endif

@if ($demande->texte_a_inscrire)
**{{ __('courriels.champs.texte') }}** — {{ $demande->texte_a_inscrire }}
@endif

@if ($demande->couleurs)
**{{ __('courriels.champs.couleurs') }}** — {{ collect($demande->couleurs)->map(fn ($c) => config("fleora.palettes.{$c}.libelle", $c))->join(', ') }}
@endif

@if ($demande->creationReference)
**{{ __('courriels.champs.inspiration') }}** — {{ $demande->creationReference->t('titre') }}
@endif

@if ($demande->attachments->isNotEmpty())
**{{ __('courriels.champs.images') }}** — {{ trans_choice('courriels.champs.images_count', $demande->attachments->count()) }}
@endif

@if ($demande->estUrgente())
<x-mail::panel>
{{ __('courriels.confirmation.delai_serre') }}
</x-mail::panel>
@endif

<x-mail::button :url="route('creations')">
{{ __('courriels.confirmation.cta') }}
</x-mail::button>

{{ __('courriels.confirmation.signature') }}
{{ config('app.name') }}

<x-slot:subcopy>
{{ __('courriels.confirmation.subcopy', [
    'courriel' => config('fleora.contact.courriel'),
    'numero' => $demande->numero_suivi,
]) }}
</x-slot:subcopy>
</x-mail::message>
