<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Base de développement complète, en une commande.
     *
     * C'est ce que rejoue `make fresh` : un compte d'administration, le
     * contenu de démonstration et ses traductions. Après un `migrate:fresh`,
     * le site local est immédiatement utilisable — sans avoir à recréer un
     * compte à la main, ce qui était le cas avant.
     *
     * Les identifiants viennent du `.env` (FLEORA_ADMIN_COURRIEL et
     * FLEORA_ADMIN_MOT_DE_PASSE) : ils dépendent de la machine, pas du dépôt,
     * et n'ont donc rien à faire dans le code versionné.
     *
     * En production, voir ProductionSeeder — celui-ci refuse de s'exécuter.
     */
    public function run(): void
    {
        // `db:seed` sans --class exécute ce seeder : c'est la commande qu'on
        // tape par réflexe. En production, elle créerait un accès à
        // l'administration avec un mot de passe connu du dépôt. Le refus est
        // ici plutôt que dans la documentation : un avertissement se lit une
        // fois, un garde-fou protège à chaque exécution.
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DatabaseSeeder crée un compte d’administration de développement : '
                .'il ne doit jamais tourner en production. '
                .'Utiliser `db:seed --class=ProductionSeeder`.'
            );
        }

        $this->creerAdministrateur();

        // Contenu de démonstration : sans lui, une base fraîche donne un site
        // vide, où rien ne peut être vérifié visuellement.
        $this->call([
            DemoSeeder::class,
            TraductionsSeeder::class,
        ]);
    }

    private function creerAdministrateur(): void
    {
        $courriel = config('fleora.admin.courriel');
        $motDePasse = config('fleora.admin.mot_de_passe');

        if (blank($courriel) || blank($motDePasse)) {
            $this->command?->warn(
                'Aucun compte créé : renseigner FLEORA_ADMIN_COURRIEL et '
                .'FLEORA_ADMIN_MOT_DE_PASSE dans le .env (voir .env.example).'
            );

            return;
        }

        // updateOrCreate : rejouer le seed sur une base existante remet le mot
        // de passe du .env plutôt que d'échouer sur l'unicité du courriel.
        $utilisateur = User::updateOrCreate(
            ['email' => $courriel],
            [
                'name' => config('fleora.admin.nom'),
                'password' => $motDePasse,
                'actif' => true,
            ],
        );

        $this->command?->info("Administration : {$utilisateur->email}");
    }
}
