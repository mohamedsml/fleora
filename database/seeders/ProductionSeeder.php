<?php

namespace Database\Seeders;

use App\Models\Creation;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Contenu initial du site en production.
 *
 * Lancé une seule fois après la mise en ligne, puis rejouable sans risque :
 * tout passe par `updateOrCreate`, et rien n'écrase une saisie faite depuis
 * l'administration.
 *
 *     php artisan db:seed --class=ProductionSeeder --force
 *
 * Trois différences avec DemoSeeder, qui reste réservé au développement :
 *
 * 1. Les créations sont chargées en BROUILLON. Elles sont fictives — titres
 *    inventés, prix approximatifs, aucune photo. Publiées, elles annonceraient
 *    un catalogue qui n'existe pas ; en brouillon elles servent de gabarits à
 *    remplir depuis /admin. Une galerie vide est plus honnête qu'une galerie
 *    trompeuse, et sur un site vitrine la photo est le premier facteur de
 *    conversion.
 *
 * 2. Le vrai compte Instagram remplace l'adresse inventée par DemoSeeder, qui
 *    aurait produit un lien mort dans le pied de page et dans les données
 *    structurées.
 *
 * 3. Aucun utilisateur de test n'est créé — contrairement au DatabaseSeeder,
 *    qui ne doit jamais toucher la production.
 *
 * Les occasions, les types de produit, les FAQ et les réglages sont en
 * revanche du contenu réel : ils font vivre les filtres de la galerie et les
 * pages d'occasion, qui sont les pages à plus forte intention d'achat.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Le contenu structurel est identique à celui du développement : le
        // catalogue d'occasions et de types est réel, les FAQ sont rédigées.
        $this->call(DemoSeeder::class);

        // Les créations de démonstration repassent en brouillon. `publie` est
        // remis à false uniquement pour celles que DemoSeeder vient de créer,
        // et jamais pour une création saisie dans l'administration.
        Creation::query()
            ->whereIn('slug_fr', $this->slugsDeDemonstration())
            ->where('publie', true)
            ->whereDoesntHave('media')
            ->update(['publie' => false]);

        // On compte l'état réel, pas le nombre de lignes modifiées : une
        // création déjà en brouillon avant le passage reste à compléter, et
        // le message doit le dire.
        $aCompleter = Creation::query()
            ->whereIn('slug_fr', $this->slugsDeDemonstration())
            ->whereDoesntHave('media')
            ->count();

        if ($aCompleter > 0) {
            $this->command?->warn(
                "{$aCompleter} création(s) de démonstration en brouillon, sans photo. "
                .'Les compléter dans /admin avant publication.'
            );
        }

        // Le vrai compte, en remplacement de l'adresse inventée par
        // DemoSeeder. Il alimente le pied de page et le champ `sameAs` des
        // données structurées — un signal de référencement local.
        SiteSetting::ecrire('instagram', 'https://www.instagram.com/fleora.ca/');

        $this->command?->info('Contenu initial chargé. Prochaine étape : '
            .'traduire avec `db:seed --class=TraductionsSeeder`.');
    }

    /**
     * Les créations posées par DemoSeeder.
     *
     * Listées explicitement pour ne jamais dépublier une pièce réelle qui
     * porterait par hasard un slug proche.
     *
     * @return list<string>
     */
    private function slugsDeDemonstration(): array
    {
        return [
            'boite-blush-ivoire',
            'coffret-prenom-bleu',
            'boite-ronde-pivoines',
            'lot-invites-mariage',
            'coffret-graduation',
            'boite-automne',
        ];
    }
}
