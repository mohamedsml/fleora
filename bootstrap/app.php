<?php

use App\Http\Middleware\EnregistrerVisite;
use App\Http\Middleware\EnTetesSecurite;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ajouté au groupe `web` plutôt qu'au groupe de routes localisées :
        // Livewire envoie ses requêtes à /livewire/update, qui n'a pas de
        // préfixe de langue. Sans cela, la galerie et le formulaire
        // repasseraient en français à chaque interaction sur une page anglaise.
        $middleware->appendToGroup('web', SetLocale::class);

        // En-têtes de sécurité sur toutes les réponses HTTP, y compris les
        // pages d'erreur et l'administration.
        $middleware->append(EnTetesSecurite::class);

        // Statistiques de visite. Écrit après l'envoi de la réponse : le
        // visiteur n'attend jamais la mesure.
        $middleware->appendToGroup('web', EnregistrerVisite::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
