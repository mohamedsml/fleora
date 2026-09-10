<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Export quotidien de la base.
 *
 * Hostinger fournit des sauvegardes automatiques, mais leur granularité est
 * faible : une restauration peut coûter plusieurs jours de demandes clientes.
 * Sous la Loi 25, la perte de ces données est un incident de confidentialité
 * à consigner.
 */
class Sauvegarder extends Command
{
    protected $signature = 'fleora:sauvegarder {--jours=14 : Durée de conservation des sauvegardes}';

    protected $description = 'Exporte la base de données dans ~/backups/fleora';

    public function handle(): int
    {
        $dossier = env('FLEORA_BACKUP_DIR', dirname(base_path()).'/backups/fleora');

        if (! is_dir($dossier) && ! mkdir($dossier, 0750, true)) {
            $this->error("Impossible de créer {$dossier}");

            return self::FAILURE;
        }

        $fichier = $dossier.'/db-'.now()->format('Y-m-d_His').'.sql.gz';

        // Le mot de passe passe par l'environnement du processus, jamais en
        // argument : la ligne de commande est visible par tout utilisateur du
        // serveur via `ps`.
        $processus = Process::fromShellCommandline(
            'mysqldump --no-tablespaces --single-transaction '
            .'-h"$DB_H" -u"$DB_U" "$DB_N" | gzip > "$OUT"'
        );

        $processus->setEnv([
            'DB_H' => config('database.connections.mysql.host'),
            'DB_U' => config('database.connections.mysql.username'),
            'DB_N' => config('database.connections.mysql.database'),
            'MYSQL_PWD' => config('database.connections.mysql.password'),
            'OUT' => $fichier,
        ]);

        $processus->setTimeout(300);
        $processus->run();

        if (! $processus->isSuccessful()) {
            $this->error('Échec : '.trim($processus->getErrorOutput()));

            return self::FAILURE;
        }

        $taille = is_file($fichier) ? round(filesize($fichier) / 1024, 1) : 0;
        $this->info("Sauvegarde : {$fichier} ({$taille} Ko)");

        $this->rotation($dossier, (int) $this->option('jours'));

        return self::SUCCESS;
    }

    /**
     * Une sauvegarde jamais restaurée n'est pas une sauvegarde : penser à
     * tester une restauration sur une base jetable, pendant que tout va bien.
     */
    private function rotation(string $dossier, int $jours): void
    {
        $limite = now()->subDays($jours)->timestamp;
        $supprimes = 0;

        foreach (glob($dossier.'/db-*.sql.gz') ?: [] as $ancien) {
            if (filemtime($ancien) < $limite && unlink($ancien)) {
                $supprimes++;
            }
        }

        if ($supprimes > 0) {
            $this->line("Rotation : {$supprimes} sauvegarde(s) de plus de {$jours} jours supprimée(s).");
        }
    }
}
