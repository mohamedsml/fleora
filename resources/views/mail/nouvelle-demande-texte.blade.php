{{-- Version texte brut de la notification interne.

     Même raison que pour la confirmation : sans version texte, le text/plain
     dérivé du Markdown conserve le balisage — ici le tableau, illisible une
     fois aplati — et le ratio texte/HTML fait chuter la délivrabilité.

     Les clés de traduction sont partagées avec la version HTML. --}}
{{ $demande->numero_suivi }}
@if ($demande->estUrgente())

/!\ {{ __('courriels.nouvelle.alerte_urgence') }}
@endif

{{ __('courriels.nouvelle.cliente') }}

{{ $demande->nom }}
{{ $demande->courriel }}
@if ($demande->telephone)
{{ $demande->telephone }}
@endif
@if ($demande->ville)
{{ $demande->ville }}
@endif
@if ($demande->moyen_prefere)
{{ __('courriels.champs.moyen_prefere') }} : {{ __('demande.moyens.'.$demande->moyen_prefere) }}
@endif

{{ __('courriels.nouvelle.projet') }}

{{ __('courriels.champs.occasion') }} : {{ $demande->occasionLibelle() ?? '—' }}
{{ __('courriels.champs.date') }} : {{ $demande->date_evenement?->translatedFormat('j F Y') ?? '—' }}
{{ __('courriels.champs.type') }} : {{ $demande->productType?->t('nom') ?? '—' }}
{{ __('courriels.champs.quantite') }} : {{ $demande->quantite ? __('demande.quantites.'.$demande->quantite) : '—' }}
{{ __('courriels.champs.budget') }} : {{ $demande->budget ? __('demande.budgets.'.$demande->budget) : '—' }}
{{ __('courriels.champs.fleurs') }} : {{ $demande->fleurs ? __('demande.fleurs_options.'.$demande->fleurs) : '—' }}
{{ __('courriels.champs.theme') }} : {{ $demande->theme ? __('demande.themes.'.$demande->theme) : '—' }}
{{ __('courriels.champs.texte') }} : {{ $demande->texte_a_inscrire ?? '—' }}
{{ __('courriels.champs.couleurs') }} : {{ $demande->couleurs ? collect($demande->couleurs)->map(fn ($c) => config("fleora.palettes.{$c}.libelle", $c))->join(', ') : '—' }}
@if ($demande->creationReference)
{{ __('courriels.champs.inspiration') }} : {{ $demande->creationReference->t('titre') }}
@endif
{{ __('courriels.champs.source') }} : {{ $demande->source ? __('demande.sources.'.$demande->source) : '—' }}
@if ($demande->commentaires)

{{ __('courriels.champs.commentaires') }}

{{ $demande->commentaires }}
@endif
@if ($demande->attachments->isNotEmpty())

{{ __('courriels.champs.images') }} : {{ trans_choice('courriels.champs.images_count', $demande->attachments->count()) }}
{{ __('courriels.nouvelle.images_admin') }}
@endif

{{ __('courriels.nouvelle.cta') }} : {{ url('/admin') }}

--
{{ __('courriels.nouvelle.subcopy') }}
