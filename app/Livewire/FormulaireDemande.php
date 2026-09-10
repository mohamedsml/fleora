<?php

namespace App\Livewire;

use App\Mail\ConfirmationDemande;
use App\Mail\NouvelleDemande;
use App\Models\Creation;
use App\Models\CustomRequest;
use App\Models\Occasion;
use App\Models\ProductType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Demande de soumission — la conversion du site.
 *
 * Découpé en trois étapes pour une raison mesurable : une liste de quinze
 * champs fait fuir, alors que quelqu'un qui a franchi l'étape 1 est engagé.
 *
 * L'étape 1 ne demande AUCUNE donnée personnelle : on parle du projet, ce qui
 * est agréable, avant de demander le courriel, ce qui est un coût pour le
 * visiteur.
 */
class FormulaireDemande extends Component
{
    use WithFileUploads;

    public int $etape = 1;

    // ── Étape 1 : le projet ─────────────────────────────────────────────
    #[Validate('nullable|exists:occasions,id')]
    public ?int $occasion_id = null;

    #[Validate('nullable|string|max:120')]
    public string $occasion_autre = '';

    #[Validate('nullable|date|after_or_equal:today')]
    public string $date_evenement = '';

    #[Validate('nullable|exists:product_types,id')]
    public ?int $product_type_id = null;

    /** Vrai quand la visiteuse coche « je ne sais pas encore ». */
    public bool $type_indecis = false;

    /**
     * Vrai quand « Autre » est explicitement choisi pour l'occasion.
     *
     * Sans ce drapeau, `occasion_id === null` servait à la fois d'état initial
     * et de choix « Autre » : le bouton apparaissait sélectionné dès
     * l'ouverture, et le champ de précision s'affichait sans raison.
     */
    public bool $occasion_autre_choisie = false;

    #[Validate('nullable|string|max:16')]
    public string $quantite = '';

    // ── Étape 2 : la personnalisation (tout optionnel) ──────────────────
    #[Validate('nullable|string|max:60')]
    public string $texte_a_inscrire = '';

    /** @var array<int, string> */
    public array $couleurs = [];

    #[Validate('nullable|string|max:64')]
    public string $theme = '';

    #[Validate('nullable|in:artificielles,naturelles,a_conseiller')]
    public string $fleurs = '';

    #[Validate('nullable|string|max:32')]
    public string $budget = '';

    #[Validate('nullable|string|max:2000')]
    public string $commentaires = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $inspirations = [];

    // ── Étape 3 : les coordonnées ───────────────────────────────────────
    #[Validate('required|string|max:120')]
    public string $nom = '';

    #[Validate('required|email:rfc,dns|max:180')]
    public string $courriel = '';

    #[Validate('nullable|string|max:32')]
    public string $telephone = '';

    #[Validate('nullable|string|max:120')]
    public string $ville = '';

    #[Validate('nullable|in:courriel,texto,whatsapp,appel')]
    public string $moyen_prefere = '';

    #[Validate('nullable|string|max:64')]
    public string $source = '';

    #[Validate('accepted')]
    public bool $consentement = false;

    public bool $infolettre = false;

    // ── Anti-spam ───────────────────────────────────────────────────────
    /** Champ leurre, masqué en CSS. Un humain ne le remplit jamais. */
    public string $site_web = '';

    /** Horodatage d'ouverture : un envoi en moins de 3 s vient d'un bot. */
    public int $ouvert_a = 0;

    /** Référence de la création cliquée dans la galerie, s'il y en a une. */
    public ?Creation $creationReference = null;

    public function mount(?string $creation = null): void
    {
        $this->ouvert_a = now()->timestamp;

        if ($creation) {
            $this->creationReference = Creation::publie()
                ->with('occasions')
                ->where('slug_fr', $creation)
                ->first();

            // Pré-remplissage contextuel : une friction supprimée gratuitement.
            if ($this->creationReference) {
                $this->product_type_id = $this->creationReference->product_type_id;
                $this->occasion_id = $this->creationReference->occasions->first()?->id;
                $this->couleurs = $this->creationReference->couleurs ?? [];
            }
        }
    }

    /**
     * Règles de l'étape courante uniquement : valider l'étape 3 alors que la
     * visiteuse est à l'étape 1 afficherait des erreurs sur des champs qu'elle
     * n'a pas encore vus.
     */
    protected function reglesEtape(int $etape): array
    {
        return match ($etape) {
            1 => [
                'occasion_id' => ['nullable', Rule::exists('occasions', 'id')],
                'date_evenement' => ['nullable', 'date', 'after_or_equal:today'],
                'quantite' => ['nullable', 'string', 'max:16'],
            ],
            2 => [
                'texte_a_inscrire' => ['nullable', 'string', 'max:60'],
                'commentaires' => ['nullable', 'string', 'max:2000'],
                'inspirations' => ['array', 'max:'.config('fleora.demandes.max_pieces_jointes')],
                'inspirations.*' => [
                    'image',
                    'mimes:jpg,jpeg,png,webp,heic,heif',
                    'max:'.(config('fleora.demandes.max_piece_jointe_mo') * 1024),
                ],
            ],
            3 => [
                'nom' => ['required', 'string', 'max:120'],
                'courriel' => ['required', 'email:rfc', 'max:180'],
                'telephone' => ['nullable', 'string', 'max:32'],
                'consentement' => ['accepted'],
            ],
            default => [],
        };
    }

    public function suivant(): void
    {
        $this->validate($this->reglesEtape($this->etape));

        $this->etape = min(3, $this->etape + 1);
    }

    public function precedent(): void
    {
        $this->etape = max(1, $this->etape - 1);
    }

    public function choisirOccasion(?int $id): void
    {
        $this->occasion_id = $id;
        $this->occasion_autre_choisie = $id === null;

        if ($id !== null) {
            $this->occasion_autre = '';
        }
    }

    public function basculerCouleur(string $couleur): void
    {
        $this->couleurs = in_array($couleur, $this->couleurs, true)
            ? array_values(array_diff($this->couleurs, [$couleur]))
            : [...$this->couleurs, $couleur];
    }

    /**
     * Alerte douce quand l'événement est proche : mieux vaut annoncer le délai
     * serré tout de suite que décevoir après coup.
     */
    public function getDelaiServeProperty(): bool
    {
        if (! $this->date_evenement) {
            return false;
        }

        return now()->diffInDays($this->date_evenement, false) < 10;
    }

    public function envoyer(): void
    {
        $this->validate($this->reglesEtape(3));

        if ($this->estUnBot()) {
            // On ne dit pas au bot qu'il est repéré : même écran de
            // confirmation, rien en base.
            $this->redirectRoute('merci', navigate: true);

            return;
        }

        // 3 demandes par heure et par IP : une cliente légitime en envoie
        // rarement plus, un script en tente beaucoup plus.
        $cle = 'demande:'.request()->ip();

        if (RateLimiter::tooManyAttempts($cle, 3)) {
            $this->addError('courriel', __('demande.trop_de_tentatives'));

            return;
        }

        RateLimiter::hit($cle, 3600);

        $demande = CustomRequest::create([
            'occasion_id' => $this->occasion_id,
            'occasion_autre' => $this->occasion_autre ?: null,
            'date_evenement' => $this->date_evenement ?: null,
            'product_type_id' => $this->type_indecis ? null : $this->product_type_id,
            'quantite' => $this->quantite ?: null,
            'texte_a_inscrire' => $this->texte_a_inscrire ?: null,
            'couleurs' => $this->couleurs ?: null,
            'theme' => $this->theme ?: null,
            'fleurs' => $this->fleurs ?: null,
            'budget' => $this->budget ?: null,
            'commentaires' => $this->commentaires ?: null,
            'creation_reference_id' => $this->creationReference?->id,
            'nom' => $this->nom,
            'courriel' => $this->courriel,
            'telephone' => $this->telephone ?: null,
            'ville' => $this->ville ?: null,
            'moyen_prefere' => $this->moyen_prefere ?: null,
            'source' => $this->source ?: null,
            'consentement' => true,
            // Horodaté : c'est ce qui est opposable sous la Loi 25.
            'consentement_le' => now(),
            'infolettre' => $this->infolettre,
            'langue' => app()->getLocale(),
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 512),
        ]);

        $this->enregistrerInspirations($demande);

        $demande->load(['occasion', 'productType', 'creationReference', 'attachments']);

        // Deux courriels : réassurance immédiate pour la cliente, alerte pour
        // l'atelier. Envoyés en file d'attente pour que la page de
        // remerciement s'affiche sans attendre le serveur SMTP.
        Mail::to($demande->courriel)->queue(new ConfirmationDemande($demande));
        Mail::to(config('fleora.contact.courriel'))->queue(new NouvelleDemande($demande));

        session()->put('demande_numero', $demande->numero_suivi);

        $this->redirectRoute('merci', navigate: true);
    }

    /**
     * Deux signaux, aucun ne demandant d'effort à la visiteuse : le champ
     * leurre et le temps de remplissage.
     */
    private function estUnBot(): bool
    {
        if ($this->site_web !== '') {
            return true;
        }

        return (now()->timestamp - $this->ouvert_a) < 3;
    }

    private function enregistrerInspirations(CustomRequest $demande): void
    {
        foreach ($this->inspirations as $fichier) {
            $chemin = $fichier->store("demandes/{$demande->numero_suivi}", 'local');

            $demande->attachments()->create([
                'chemin' => $chemin,
                // Nom d'origine conservé pour l'affichage seulement — jamais
                // utilisé comme chemin de fichier.
                'nom_original' => substr($fichier->getClientOriginalName(), 0, 255),
                'mime' => $fichier->getMimeType(),
                'taille' => Storage::disk('local')->size($chemin),
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.formulaire-demande', [
            'occasions' => Occasion::publie()->get(),
            'types' => ProductType::publie()->get(),
            'palettes' => config('fleora.palettes'),
        ]);
    }
}
