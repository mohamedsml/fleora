<?php

namespace Database\Seeders;

use App\Models\Occasion;
use Illuminate\Database\Seeder;

/**
 * Textes des pages d'occasion, issus du document de refonte.
 *
 *     php artisan db:seed --class=TextesOccasionsSeeder --force
 *
 * Ne remplit que les champs VIDES : une saisie faite depuis l'administration
 * n'est jamais écrasée, et le seeder peut être rejoué sans risque.
 *
 * La propriété `$remplacer` écrase aussi les introductions déjà remplies.
 * Elle sert à substituer les textes génériques du seeder de démonstration par
 * ceux du document, et s'active depuis la commande `fleora:textes-occasions`.
 *
 * Le libellé du bouton est propre à chaque occasion. « Créer pour mon
 * mariage » reprend les mots de la visiteuse et confirme qu'elle est au bon
 * endroit, là où un « Créer ma Fleora » générique la laisse douter. L'article
 * varie selon le mot (mon / un / une), d'où une valeur par occasion plutôt
 * qu'une formule composée automatiquement.
 */
class TextesOccasionsSeeder extends Seeder
{
    /**
     * Écrase les champs déjà remplis. Réservé au remplacement des textes de
     * démonstration : à activer explicitement.
     */
    public bool $remplacer = false;

    public function run(): void
    {
        $remplaces = 0;

        foreach ($this->textes() as $slug => $valeurs) {
            $occasion = Occasion::where('slug_fr', $slug)->first();

            if (! $occasion) {
                $this->command?->warn("Occasion « {$slug} » absente — ignorée.");

                continue;
            }

            $aEcrire = [];

            foreach ($valeurs as $colonne => $valeur) {
                // Les boutons sont toujours posés : ils n'existaient pas avant,
                // donc rien à écraser. Les introductions ne le sont que sur
                // demande, parce que le seeder de démonstration les a déjà
                // remplies de textes génériques.
                $vide = blank($occasion->{$colonne});
                $ecrasable = $this->remplacer && str_starts_with($colonne, 'intro_');

                if ($vide || $ecrasable) {
                    $aEcrire[$colonne] = $valeur;

                    if (! $vide) {
                        $remplaces++;
                    }
                }
            }

            if ($aEcrire !== []) {
                $occasion->update($aEcrire);
            }
        }

        $this->command?->info('Textes des occasions appliqués.');

        if ($remplaces > 0) {
            $this->command?->warn("{$remplaces} texte(s) existants remplacés.");
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function textes(): array
    {
        return [
            'mariage' => [
                'intro_fr' => 'Des créations florales et cadeaux personnalisés imaginés sur mesure pour votre mariage. '
                    .'Créées avec soin dans la région de Montréal, chaque pièce est pensée pour s’harmoniser à votre journée.',
                'intro_en' => 'Floral creations and personalised gifts made to measure for your wedding. '
                    .'Created with care in the Montreal area, each piece is designed to match your day.',
                'cta_fr' => 'Créer pour mon mariage',
                'cta_en' => 'Create for my wedding',
            ],

            'baby-shower' => [
                'intro_fr' => 'Des créations délicates et personnalisées pour célébrer l’arrivée de bébé. '
                    .'Choisissez les couleurs, les fleurs et les petits détails qui rendront votre attention unique.',
                'intro_en' => 'Delicate, personalised creations to celebrate a baby’s arrival. '
                    .'Choose the colours, the flowers and the small details that make your gift unique.',
                'cta_fr' => 'Créer pour mon baby shower',
                'cta_en' => 'Create for my baby shower',
            ],

            'bapteme' => [
                'intro_fr' => 'Des créations florales et cadeaux personnalisés pour célébrer ce moment précieux en famille. '
                    .'Chaque détail est choisi avec soin pour créer une attention douce, élégante et mémorable.',
                'intro_en' => 'Floral creations and personalised gifts to celebrate this precious family moment. '
                    .'Every detail is chosen with care for a gentle, elegant and memorable gift.',
                'cta_fr' => 'Créer pour un baptême',
                'cta_en' => 'Create for a baptism',
            ],

            'graduation' => [
                'intro_fr' => 'Célébrez une grande réussite avec une création pensée spécialement pour cette personne. '
                    .'Fleurs, couleurs et détails personnalisés : une façon élégante de dire « Je suis fier·ère de toi ».',
                'intro_en' => 'Celebrate a great achievement with a creation made especially for them. '
                    .'Flowers, colours and personalised details: an elegant way to say “I’m proud of you”.',
                'cta_fr' => 'Créer pour une graduation',
                'cta_en' => 'Create for a graduation',
            ],

            'cadeau-personnalise' => [
                'intro_fr' => 'Vous cherchez un cadeau qui ne ressemble à aucun autre ? '
                    .'Nous créons une composition florale et cadeau sur mesure, inspirée de la personne, '
                    .'de son style et du message que vous souhaitez lui transmettre.',
                'intro_en' => 'Looking for a gift unlike any other? '
                    .'We create a bespoke floral and gift composition, inspired by the person, '
                    .'their style and the message you want to convey.',
                'cta_fr' => 'Créer un cadeau',
                'cta_en' => 'Create a gift',
            ],

            // Le document ne donne pas de texte pour l'anniversaire, absent de
            // sa liste. Seul le bouton est posé, pour la cohérence.
            'anniversaire' => [
                'cta_fr' => 'Créer pour un anniversaire',
                'cta_en' => 'Create for a birthday',
            ],
        ];
    }
}
