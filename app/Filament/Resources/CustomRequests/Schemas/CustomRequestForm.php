<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\Enums\StatutDemande;
use App\Models\CustomRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Écran d'une demande.
 *
 * Ce que la cliente a écrit est en LECTURE SEULE : c'est une déclaration
 * horodatée, pas un brouillon interne. Modifier « budget : 150-300 » en
 * « 300+ » effacerait ce qu'elle a réellement demandé — et sous la Loi 25,
 * l'exactitude des renseignements collectés est une obligation.
 *
 * Seul le suivi commercial — statut, notes, date de réponse — est modifiable.
 */
class CustomRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Suivi')
                    ->description('La seule partie modifiable : le reste vient de la cliente.')
                    ->schema([
                        Select::make('statut')
                            ->label('Statut')
                            ->options(StatutDemande::class)
                            ->required()
                            ->native(false),

                        DateTimePicker::make('repondu_le')
                            ->label('Répondu le')
                            ->seconds(false)
                            ->helperText('Renseigné automatiquement au premier changement de statut.'),

                        Textarea::make('notes_internes')
                            ->label('Notes internes')
                            ->rows(4)
                            ->helperText('Jamais visible par la cliente.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Cliente')
                    ->schema([
                        TextEntry::make('nom')->label('Nom'),
                        TextEntry::make('courriel')
                            ->label('Courriel')
                            ->copyable()
                            // Un clic ouvre la réponse, avec le numéro de suivi
                            // déjà dans l'objet.
                            ->url(fn (CustomRequest $r) => 'mailto:'.$r->courriel
                                .'?subject='.rawurlencode('Votre demande '.$r->numero_suivi)),
                        TextEntry::make('telephone')
                            ->label('Téléphone')
                            ->copyable()
                            ->url(fn (CustomRequest $r) => $r->telephone ? 'tel:'.$r->telephone : null)
                            ->placeholder('—'),
                        TextEntry::make('ville')->label('Ville')->placeholder('—'),
                        TextEntry::make('moyen_prefere')
                            ->label('Préfère être joint par')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.moyens.'.$state) : null)
                            ->placeholder('—'),
                        TextEntry::make('source')
                            ->label('Nous a connus par')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.sources.'.$state) : null)
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make('Projet')
                    ->schema([
                        TextEntry::make('numero_suivi')
                            ->label('Numéro de suivi')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('occasion')
                            ->label('Occasion')
                            ->state(fn (CustomRequest $r) => $r->occasionLibelle())
                            ->placeholder('—'),
                        TextEntry::make('date_evenement')
                            ->label('Date de l’événement')
                            ->date('j F Y')
                            ->placeholder('—')
                            ->helperText(fn (CustomRequest $r) => $r->estUrgente()
                                ? '⚠ Événement dans moins de 14 jours, sans réponse'
                                : null),
                        TextEntry::make('productType.nom_fr')->label('Type')->placeholder('À conseiller'),
                        TextEntry::make('quantite')
                            ->label('Quantité')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.quantites.'.$state) : null)
                            ->placeholder('—'),
                        TextEntry::make('budget')
                            ->label('Budget')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.budgets.'.$state) : null)
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make('Personnalisation')
                    ->schema([
                        TextEntry::make('texte_a_inscrire')->label('Prénom ou texte')->placeholder('—'),
                        TextEntry::make('theme')
                            ->label('Style')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.themes.'.$state) : null)
                            ->placeholder('—'),
                        TextEntry::make('fleurs')
                            ->label('Fleurs')
                            ->formatStateUsing(fn (?string $state) => $state ? __('demande.fleurs_options.'.$state) : null)
                            ->placeholder('—'),
                        TextEntry::make('couleurs')
                            ->label('Palette')
                            // Pastilles plutôt que des libellés : on juge un
                            // accord de couleurs à l'œil, pas à la lecture.
                            ->state(fn (CustomRequest $r) => static::pastilles($r->couleurs))
                            ->html()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('creationReference.titre_fr')
                            ->label('Inspirée de')
                            ->url(fn (CustomRequest $r) => $r->creationReference
                                ? route('creations.show', $r->creationReference->slug_fr)
                                : null)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('commentaires')
                            ->label('Commentaires')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Images d’inspiration')
                    ->schema([
                        TextEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn (CustomRequest $r) => static::vignettes($r))
                            ->html()
                            ->placeholder('Aucune image jointe.'),
                    ])
                    ->visible(fn (?CustomRequest $record) => $record?->attachments->isNotEmpty()),

                Section::make('Traçabilité')
                    ->description('Conservé pour la Loi 25 et le diagnostic anti-spam.')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('consentement_le')
                            ->label('Consentement donné le')
                            ->dateTime('j F Y à H:i')
                            ->placeholder('—'),
                        TextEntry::make('infolettre')
                            ->label('Infolettre')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Acceptée' : 'Refusée'),
                        TextEntry::make('langue')->label('Langue'),
                        TextEntry::make('created_at')->label('Reçue le')->dateTime('j F Y à H:i'),
                        TextEntry::make('ip')->label('Adresse IP')->placeholder('—'),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * Aperçu visuel de la palette demandée.
     */
    private static function pastilles(?array $couleurs): ?HtmlString
    {
        if (blank($couleurs)) {
            return null;
        }

        $html = collect($couleurs)
            ->map(function (string $cle): string {
                $palette = config("fleora.palettes.{$cle}");
                $hex = $palette['hex'] ?? '#ccc';
                $libelle = e($palette['libelle'] ?? $cle);

                return '<span style="display:inline-flex;align-items:center;gap:.5rem;'
                    .'margin:0 .75rem .5rem 0;font-size:.875rem">'
                    .'<span style="width:1.25rem;height:1.25rem;border-radius:9999px;'
                    ."background:{$hex};border:1px solid rgba(0,0,0,.1)\"></span>{$libelle}</span>";
            })
            ->implode('');

        return new HtmlString($html);
    }

    /**
     * Vignettes des images jointes, via des URL signées à durée limitée.
     */
    private static function vignettes(CustomRequest $demande): ?HtmlString
    {
        if ($demande->attachments->isEmpty()) {
            return null;
        }

        $html = $demande->attachments
            ->map(function ($piece): string {
                // URL signée valable 15 minutes : les images d'inspiration sont
                // sur un disque privé, jamais servies publiquement.
                $url = e($piece->urlTemporaire());
                $nom = e($piece->nom_original ?? 'image');

                return '<a href="'.$url.'" target="_blank" rel="noopener" '
                    .'style="display:inline-block;margin:0 .75rem .75rem 0">'
                    .'<img src="'.$url.'" alt="'.$nom.'" '
                    .'style="width:8rem;height:8rem;object-fit:cover;border-radius:.5rem;'
                    .'border:1px solid rgba(0,0,0,.1)"></a>';
            })
            ->implode('');

        return new HtmlString($html);
    }
}
