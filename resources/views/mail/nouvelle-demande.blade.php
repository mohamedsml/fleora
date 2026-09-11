<x-mail::message>
# {{ $demande->numero_suivi }}

@if ($demande->estUrgente())
<x-mail::panel>
⚠️ **{{ __('courriels.nouvelle.alerte_urgence') }}**
</x-mail::panel>
@endif

## {{ __('courriels.nouvelle.cliente') }}

**{{ $demande->nom }}**
{{ $demande->courriel }}
@if ($demande->telephone){{ $demande->telephone }}@endif
@if ($demande->ville){{ $demande->ville }}@endif
@if ($demande->moyen_prefere)
{{ __('courriels.champs.moyen_prefere') }} : **{{ __('demande.moyens.'.$demande->moyen_prefere) }}**
@endif

## {{ __('courriels.nouvelle.projet') }}

| | |
|---|---|
| {{ __('courriels.champs.occasion') }} | {{ $demande->occasionLibelle() ?? '—' }} |
| {{ __('courriels.champs.date') }} | {{ $demande->date_evenement?->translatedFormat('j F Y') ?? '—' }} |
| {{ __('courriels.champs.type') }} | {{ $demande->productType?->t('nom') ?? '—' }} |
| {{ __('courriels.champs.quantite') }} | {{ $demande->quantite ? __('demande.quantites.'.$demande->quantite) : '—' }} |
| {{ __('courriels.champs.budget') }} | {{ $demande->budget ? __('demande.budgets.'.$demande->budget) : '—' }} |
| {{ __('courriels.champs.fleurs') }} | {{ $demande->fleurs ? __('demande.fleurs_options.'.$demande->fleurs) : '—' }} |
| {{ __('courriels.champs.theme') }} | {{ $demande->theme ? __('demande.themes.'.$demande->theme) : '—' }} |
| {{ __('courriels.champs.texte') }} | {{ $demande->texte_a_inscrire ?? '—' }} |
| {{ __('courriels.champs.couleurs') }} | {{ $demande->couleurs ? collect($demande->couleurs)->map(fn ($c) => config("fleora.palettes.{$c}.libelle", $c))->join(', ') : '—' }} |
@if ($demande->creationReference)
| {{ __('courriels.champs.inspiration') }} | {{ $demande->creationReference->t('titre') }} |
@endif
| {{ __('courriels.champs.source') }} | {{ $demande->source ? __('demande.sources.'.$demande->source) : '—' }} |

@if ($demande->commentaires)
## {{ __('courriels.champs.commentaires') }}

{{ $demande->commentaires }}
@endif

@if ($demande->attachments->isNotEmpty())
## {{ __('courriels.champs.images') }}

{{ trans_choice('courriels.champs.images_count', $demande->attachments->count()) }} —
{{ __('courriels.nouvelle.images_admin') }}
@endif

{{-- Lien vers l'accueil du back-office plutôt que vers la fiche : l'URL d'une
     ressource Filament dépend de sa route nommée, qui changerait sans qu'un
     courriel déjà envoyé puisse être corrigé. --}}
<x-mail::button :url="url('/admin')">
{{ __('courriels.nouvelle.cta') }}
</x-mail::button>

<x-slot:subcopy>
{{ __('courriels.nouvelle.subcopy') }}
</x-slot:subcopy>
</x-mail::message>
