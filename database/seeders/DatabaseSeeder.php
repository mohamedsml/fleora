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
     * Données de développement uniquement.
     *
     * Ce seeder est celui qu'exécute `db:seed` sans `--class` : c'est la
     * commande qu'on tape par réflexe. En production, il créerait un compte
     * `test@example.com` dont le mot de passe vient d'une factory — donc un
     * accès à l'administration, aux demandes clientes et aux devis.
     *
     * Le refus est ici plutôt que dans la documentation : un avertissement se
     * lit une fois, un garde-fou protège à chaque exécution. Pour la
     * production, voir ProductionSeeder.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DatabaseSeeder crée un utilisateur de test : il ne doit jamais '
                .'tourner en production. Utiliser `db:seed --class=ProductionSeeder`.'
            );
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
