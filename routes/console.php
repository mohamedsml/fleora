<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Déclenchées par une seule entrée cron sur le serveur :
|   * * * * * cd <projet> && /opt/alt/php83/usr/bin/php artisan schedule:run
|
| Voir docs/deploiement-automatique.md — le chemin complet de PHP 8.3 est
| indispensable, `php` seul donne 8.2 en contexte non interactif.
|
*/

/*
 * Envoi des courriels en attente.
 *
 * Sur un hébergement mutualisé, aucun processus ne tourne en permanence :
 * `queue:work` serait tué. On traite donc la file par petites salves, chaque
 * minute.
 *
 * --stop-when-empty : le processus se termine dès la file vide au lieu
 *   d'attendre indéfiniment.
 * --max-time=50 : borne l'exécution sous la minute, pour ne jamais chevaucher
 *   la salve suivante.
 * withoutOverlapping : deuxième garde-fou contre deux salves simultanées, qui
 *   enverraient les courriels en double.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/*
 * Loi 25 — conservation limitée.
 *
 * Les demandes contiennent des renseignements personnels : nom, courriel,
 * téléphone, date d'événement. La politique de confidentialité annonce une
 * conservation de 24 mois après le dernier contact ; cette tâche l'applique
 * réellement, plutôt que de laisser une promesse non tenue dans un document.
 */
Schedule::command('fleora:purger-demandes')
    ->weeklyOn(1, '03:30');

/*
 * Sauvegarde de la base.
 *
 * Hostinger fournit des sauvegardes automatiques, mais leur granularité est
 * faible : une restauration peut coûter plusieurs jours de demandes clientes.
 */
Schedule::command('fleora:sauvegarder')
    ->dailyAt('03:00');
