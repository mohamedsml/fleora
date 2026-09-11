<?php

namespace App\Console\Commands;

use Database\Seeders\TextesOccasionsSeeder;
use Illuminate\Console\Command;

/**
 * Applique les textes des pages d'occasion issus du document de refonte.
 *
 *     php artisan fleora:textes-occasions              # complète les champs vides
 *     php artisan fleora:textes-occasions --remplacer  # écrase aussi les introductions
 *
 * Existe parce que `db:seed` ne transmet pas d'option au seeder : sans cette
 * commande, le remplacement demanderait de modifier le code entre deux
 * exécutions.
 */
class AppliquerTextesOccasions extends Command
{
    protected $signature = 'fleora:textes-occasions
                            {--remplacer : Écrase les introductions déjà remplies}';

    protected $description = 'Applique les textes des pages d’occasion (boutons et introductions)';

    public function handle(): int
    {
        $seeder = new TextesOccasionsSeeder;
        $seeder->setCommand($this);
        $seeder->remplacer = (bool) $this->option('remplacer');

        if ($seeder->remplacer && ! $this->option('no-interaction')
            && ! $this->confirm('Les introductions existantes seront remplacées. Continuer ?', false)) {
            $this->line('Annulé.');

            return self::SUCCESS;
        }

        $seeder->run();

        return self::SUCCESS;
    }
}
